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

async function buildStandalone({ config = {} }) {
  config = mason.config.merge(
    {
      location: pagesRoot,
      destination: `${pagesRoot}/artifacts/standalone-pages.zip`,
      framework: {
        source: 'local',
        location: path.resolve(pagesRoot, '..', 'joomlatools-framework'),
        includeComponents: false,
      },
    },
    config
  );

  const { path: tmp, cleanup } = await mason.fs.getTemporaryDirectory();
  const appDir = `${tmp}/app`;
  const vendorDir = `${appDir}/vendor`;

  mason.log.debug(`Using ${tmp} folder for standalone build`);

  // 1. composer install first so it doesn't overwrite our joomlatools packages
  await mason.fs.ensureDir(appDir);
  await fs.writeFile(
    `${appDir}/composer.json`,
    JSON.stringify(
      {
        require: {
          'michelf/php-markdown': '1.9.*',
          'symfony/yaml': '^5.4',
        },
      },
      null,
      2
    )
  );
  mason.log.debug('Running composer install…');
  await execShellCommand(`cd "${appDir}" && composer install --no-dev`);

  // 2. Build framework into app/vendor/joomlatools/framework/code/
  await buildFramework({
    config: {
      ...config.framework,
      compress: false,
      destination: `${vendorDir}/joomlatools/framework/code`,
    },
  });

  // 3. Build pages into app/vendor/joomlatools/pages/code/
  await build({
    config: {
      ...config,
      compress: false,
      destination: `${vendorDir}/joomlatools/pages/code`,
    },
  });

  // 4. Copy bootstrapper
  await mason.fs.ensureDir(`${vendorDir}/joomlatools/pages/resources/pages`);
  await fs.copyFile(
    `${pagesRoot}/resources/pages/bootstrapper.php`,
    `${vendorDir}/joomlatools/pages/resources/pages/bootstrapper.php`
  );

  // 5. Config
  await mason.fs.ensureDir(`${appDir}/config`);
  await fs.copyFile(
    `${pagesRoot}/resources/standalone/config.php`,
    `${appDir}/config/koowa.php`
  );

  // 6. Entry point — defines paths then delegates to bootstrapper
  await fs.writeFile(
    `${tmp}/index.php`,
    `<?php\ndefine('KOOWA_ROOT', __DIR__);\ndefine('KOOWA_VENDOR', __DIR__.'/app/vendor');\ndefine('KOOWA_CONFIG', __DIR__.'/app/config');\ndefine('PAGES_SITE_ROOT', __DIR__.'/sites');\nrequire KOOWA_VENDOR.'/joomlatools/pages/resources/pages/bootstrapper.php';\n`
  );

  // 7. Empty sites/ placeholder
  await mason.fs.ensureDir(`${tmp}/sites`);
  await fs.writeFile(`${tmp}/sites/.gitkeep`, '');

  // 8. Archive
  await mason.fs.ensureDir(`${pagesRoot}/artifacts`);
  await mason.fs.archiveDirectory(tmp, config.destination);

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
    buildStandalone,
    bundle,
    default: ["bundle", "buildExtensions"],
  },
};
