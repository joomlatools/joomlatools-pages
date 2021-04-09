const mason = require("@joomlatools/mason-tools-v1");
const path = require("path");
const fs = require("fs").promises;

const pagesRoot = process.cwd();

async function build({ config = {} }) {
  config = mason.config.merge(
    {
      location: pagesRoot,
      destination: `${pagesRoot}/com_pages.zip`,
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

  await mason.fs.copy(`${config.location}/LICENSE.txt`, `${pkg}/LICENSE.txt`);

  await fs.rename(
    `${pkg}/administrator/components/com_pages/pages.xml`,
    `${pkg}/pages.xml`
  );

  if (config.compress) {
    mason.log.debug(`Creating ZIP file in ${config.destination}`);

    await mason.fs.archiveDirectory(pkg, config.destination);
  } else {
    mason.log.debug(`Copying final package to ${config.destination}`);

    await mason.fs.copy(pkg, config.destination);
  }

  await cleanup();
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

  const extensionFolders = await fs.readdir(contribFolder, {
    withFileTypes: true,
  });

  await Promise.all(
    extensionFolders
      .filter((dirent) => dirent.isDirectory())
      .map((dirent) => {
        const extensionFolder = dirent.name;

        mason.log.debug(`Building extension: ${extensionFolder}…`);

        return mason.fs.archiveDirectory(
          path.join(contribFolder, extensionFolder),
          `${config.location}/com_pages-extension-${extensionFolder}.zip`
        );
      })
  );
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

  const installer = `${tmp}/installer`;

  await mason.github.download({
    token: config.githubToken,
    repo: "joomlatools/joomlatools-extension-installer",
    branch: "master",
    destination: installer,
  });

  await Promise.all([
    build({
      config: {
        ...config,
        compress: false,
        destination: `${installer}/payload/pages`,
      },
    }),
    buildFramework({
      config: {
        ...config.framework,
        compress: false,
        destination: `${installer}/payload/framework`,
      },
    }),
  ]);

  await fs.unlink(`${installer}/.gitignore`);
  await fs.unlink(`${installer}/README.md`);
  await fs.unlink(`${installer}/LICENSE`);

  await fs.writeFile(
    `${installer}/payload/manifest.json`,
    JSON.stringify(config.bundle.manifest, null, 4)
  );

  const xmlManifest = (
    await fs.readFile(`${installer}/payload/pages/pages.xml`)
  ).toString();
  const version = xmlManifest.match(/<version>(.*?)<\/version>/);

  await mason.fs.archiveDirectory(
    installer,
    `${pagesRoot}/com_pages${version ? "_v" + version[1] : "_bundle"}.zip`
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
    default: ["bundle" /*, 'buildExtensions'*/],
  },
};
