const mason = require('@joomlatools/mason-tools-v1');

async function css() {
  await mason.css.process(`input.css`, `output.css`, {});
}

async function bs() {
  mason.browserSync({
    watch: true,
    server: {
       baseDir: './joomlatools-pages/theme'
    },
    files: 'css/*.css',
  });
}

module.exports = {
  version: '1.0',
  tasks: {
    css,
    bs,
    watch: {
      path: ['.'],
      callback: async (path) => {
        if (path.endsWith('input.css')) {
          await css();
        }
      },
    },
  },
};