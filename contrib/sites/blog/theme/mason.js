const mason = require('@joomlatools/mason-tools-v1');

async function postcss() {
  await mason.css.process(`css/input.css`, `css/output.css`, {
    tailwind: {    
      purge: {
        enabled: true,
        content: [
          '../**/*.html.php',
        ],
        options: {
          safelist: [
          // These classes are used by MIX, let's add them to the safe list
          'lazyprogressive', 
          'bg-center', 
          'bg-cover', 
          'bg-right', 
          'object-center', 
          'object-right',
          // These classes are used by helper('paginator.pagination'), let's add them to the safe list
          'active',
          'k-pagination__pages',
          // These classes are used by icons, let's add them to the safe list
          'w-5',
          'h-5',
          'mr-1',
          ],
        },
      },
      theme: {
        extend: {
          colors: {
            // Joomlatools blue
            'brand': '#00adef',
            'blue': {
              50: '#f3fbff',
              100: '#e6f7fe',
              200: '#c0ebfb',
              300: '#97def9',
              400: '#4dc6f4',
              500: '#00adef',
              600: '#009ad5',
              700: '#006890',
              800: '#004e6c',
              900: '#003346'
            },
          },
        }
      },
      variants: {
        opacity: ['responsive', 'hover'],
        borderWidth: ['responsive', 'hover', 'focus'],
      },
    }
  });
}

async function sync() {
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
    postcss,
    sync,
    watch: {
      path: ['.'],
      callback: async (path) => {
        if (path.endsWith('css/input.css')) {
          await postcss();
        }
      },
    },
  },
};
