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
          // These classes are used by reveal, let's add them to the safe list
          'reveal',
          'slides',
          'controls',
          'progress',
          'backgrounds',
          'w-5',
          'w-10',
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
          // Joomlatools webfont
          fontFamily: {
            'jt': ['VAGRoundedTL-Regular', 'Arial Rounded', 'sans-serif'],
          },
          // Slide layout main grid
          gridTemplateRows: {
            'splash': '1fr auto',
            'layout': 'auto 1fr auto',
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
