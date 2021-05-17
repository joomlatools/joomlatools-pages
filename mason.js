const mason = require("@joomlatools/mason-tools-v1");
const path = require("path");
const fs = require("fs").promises;
const exec = require('child_process').exec;

const pagesRoot = process.cwd();

async function build({ config = {} }) {
  config = mason.config.merge(
    {
      location: pagesRoot,
      destination: `${pagesRoot}/artifacts/com_pages.zip`,
      compress: true,
    },
    config
  );
  const { path: tmp, cleanup } = await mason.fs.getTemporaryDirectory();
  const pkg = `${tmp}/package`;

  await fs.mkdir(pkg);

  mason.log.debug(`Using ${tmp} folder for extension build`);
  mason.log.debug(`Using ${config.location} folder for extension source`);

  await mason.fs.copyWithoutHiddenFiles(`${config.location}/code`, pkg);

  await mason.fs.ensureDir(`${config.location}/artifacts`);

  if (config.compress) {
    mason.log.debug(`Creating ZIP file in ${config.destination}`);

    await mason.fs.archiveDirectory(pkg, config.destination);
  } else {
    mason.log.debug(`Copying final package to ${config.destination}`);

    await mason.fs.copy(pkg, config.destination);
  }

  await cleanup();
}

function execShellCommand(cmd) {
  return new Promise((resolve, reject) => {
    exec(cmd, (error, stdout, stderr) => {
      if (error) {
        reject(error);
      }
      resolve(stdout ? stdout : stderr);
    });
  });
}

async function buildExtensions({ config = {} }) {
  config = mason.config.merge(
    {
      location: pagesRoot,
      folder: "contrib",
    },
    { ...config, ...(config.extension || {}) }
  );

  const contribFolder = `${config.location}/${config.folder}`;

  if (!require("fs").existsSync(contribFolder)) {
    mason.log.error(`${contribFolder} does not exist.`);
  }

  await mason.fs.ensureDir(`${config.location}/artifacts`);

  const extensionFolder = `${contribFolder}/extensions`;
  if (require("fs").existsSync(extensionFolder)) {
    const extensionFolders = await fs.readdir(extensionFolder, {
      withFileTypes: true,
    });

    await Promise.all(
        extensionFolders
            .filter((dirent) => dirent.isDirectory())
            .map(async (dirent) => {
              const folder = dirent.name;

              mason.log.debug(`Building extension: ${folder}…`);

              const cmd = `<?php
              $phar = new PharData('${config.location}/artifacts/extension-${folder}.zip');
              $phar->buildFromDirectory('${config.location}/contrib/extensions/${folder}');
              $phar->compressFiles(Phar::GZ);
              $phar->setSignatureAlgorithm(Phar::SHA256);`
                  .replace(/\s+/g, ' ')
                  .replace(/[\$]+/g, '\\$');

              return execShellCommand(`echo "${cmd}" | php`);
            })
    );

  }

  const siteFolder = `${contribFolder}/sites`;
  if (require("fs").existsSync(siteFolder)) {
    const siteFolders = await fs.readdir(siteFolder, {
      withFileTypes: true,
    });

    await Promise.all(
        siteFolders
            .filter((dirent) => dirent.isDirectory())
            .map((dirent) => {
              const folder = dirent.name;

              mason.log.debug(`Building site: ${folder}…`);

              return mason.fs.archiveDirectory(
                  path.join(siteFolder, folder),
                  `${config.location}/artifacts/site-${folder}.zip`
              );
            })
    );
  }

}

async function buildFramework({ config = {} }) {
  const projectsFolder = path.resolve(pagesRoot, "..");

  config = mason.config.merge(
    {
      source: "remote", // or 'local'
      location: `${projectsFolder}/joomlatools-framework`,
      destination: `${projectsFolder}/joomlatools-framework/joomlatools-framework.zip`,
      branch: "master",
      includeComponents: false,
    },
    { ...config, ...(config.framework || {}) }
  );

  let frameworkMasonfile;
  let tmp, cleanup;

  if (config.source !== "remote" && config.location) {
    frameworkMasonfile = `${config.location}/mason.js`;
  } else {
    ({ path: tmp, cleanup } = await mason.fs.getTemporaryDirectory());

    mason.log.debug(`Using ${tmp} folder for framework build`);

    config.location = `${tmp}/framework`;

    frameworkMasonfile = `${config.location}/mason.js`;

    await mason.github.download({
      repo: "joomlatools/joomlatools-framework",
      branch: config.branch,
      destination: config.location,
    });
  }

  const frameworkMason = await mason.core.getMasonFile(frameworkMasonfile);

  await frameworkMason.tasks.build({
    config: { ...frameworkMason.config, ...config, source: "local" },
  });

  cleanup && (await cleanup());
}

async function bundle({ config = {} }) {
  config = mason.config.merge(
    {
      githubToken: null,
      framework: {},
      bundle: {
        manifest: {},
      },
    },
    config
  );

  if (!config.githubToken) {
    throw new Error(
      "Github token is required for bundle task to download joomlatools-extension-installer"
    );
  }

  const { path: tmp, cleanup } = await mason.fs.getTemporaryDirectory();

  mason.log.debug(`Using ${tmp} folder for bundle`);

  await buildFramework({
    config: {
      ...config.framework,
      compress: false,
      destination: `${tmp}`,
    },
  });

  await build({
    config: {
      ...config,
      compress: false,
      destination: `${tmp}/libraries/joomlatools-components/pages`,
    },
  });


  const xmlManifest = (
    await fs.readFile(`${tmp}/libraries/joomlatools-components/pages/version/version.php`)
  ).toString();

  const version = xmlManifest.match(/VERSION\s*=\s*'(.*?)'/);


  await mason.fs.ensureDir(`${pagesRoot}/artifacts`);
  await mason.fs.archiveDirectory(
    tmp,
    `${pagesRoot}/artifacts/com_pages${version ? "_v" + version[1] : "_bundle"}.zip`
  );

  await cleanup();
}

module.exports = {
  version: "1.0",
  options: {
    githubToken: {
      type: "string",
      description: "Github token for packaging. Required for bundle task",
    },
  },
  tasks: {
    build,
    buildExtensions,
    buildFramework,
    bundle,
    default: ["bundle" , 'buildExtensions'],
  },
};
