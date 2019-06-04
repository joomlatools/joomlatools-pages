# Joomlatools Pages

Joomlatools Pages is a simple, static content generator for **[Joomla](http://www.joomla.org)**. It's inspired by the ideas 
and concepts of flat-file CMS systems like [Jekhyll]( https://jekyllrb.com), [Grav](https://getgrav.org/), etc. Pages takes 
your html content or renders Markdown, Twig, Joomla templates, ... spits out the result and injects it in your site.

### Why Joomlatools Pages?

For the rebuild of [Joomlatools.com](http://joomlatools.test/blog/services/introducing-our-brand-new-website) we needed a solution that allowed us to easily include and update static content into our site. Using Joomla modules would make the site very hard to manage. Pages solves this in an elegant and  simple way, and not to mention it's super fast too!

### Who is Joomlatools Pages for?

Joomlatools Pages is for website developers and designers who create bespoke Joomla sites and have experience with html, 
markdown, etc.

## Requirements

* Joomla 3.6.5 
* PHP7 or newer

## Installation

Go to the root directory of your Joomla installation in command line and execute this command:

```
composer require joomlatools/pages --ignore-platform-reqs
```

Note: You need to use the --ignore-platform-reqs flag if not the installation will fail due to a mismatch in the platform
constraint. Joomla's requires PHP 5.3.10 as minimum while Joomlatools Pages is set to minimum PHP7

## Documentation

You can find all the documentation for Joomlatools Pages [in the wiki](https://github.com/joomlatools/joomlatools-pages/wiki). Happy coding!

## Contributing

Joomlatools Pages is an open source, community-driven project. Contributions are welcome from everyone. 
We have [contributing guidelines](CONTRIBUTING.md) to help you get started.

## Contributors

See the list of [contributors](https://github.com/joomlatools/joomlatools-pages/contributors).

## License

Joomlatools Pages is open-source software licensed under the [GPLv3 license](LICENSE.txt).

## Community

Keep track of development and community news.

* Follow [@joomlatoolsdev on Twitter](https://twitter.com/joomlatoolsdev)
* Join [joomlatools/dev on Gitter](http://gitter.im/joomlatools/dev)
* Read the [Joomlatools Developer Blog](https://www.joomlatools.com/developer/blog/)
* Subscribe to the [Joomlatools Developer Newsletter](https://www.joomlatools.com/developer/newsletter/)
