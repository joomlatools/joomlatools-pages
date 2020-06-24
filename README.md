![Build package](https://github.com/joomlatools/joomlatools-pages/workflows/Build%20package/badge.svg?branch=master)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/7ba6e3e1390b488ca40f3d7458332345)](https://www.codacy.com/gh/joomlatools/joomlatools-pages?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=joomlatools/joomlatools-pages&amp;utm_campaign=Badge_Grade)

# Joomlatools Pages

### What is Joomlatools Pages?

Joomlatools Pages is an easy to use **page generator** for [Joomla](http://www.joomla.org) inspired by the ideas and concepts of flat-file CMS systems like [Grav](https://getgrav.org/) and [Statamic](statamic.com), static site generators like [Jekyll]( https://jekyllrb.com), [Hugo](https://gohugo.io), [Gatsby](https://www.gatsbyjs.org/) and Headless CMS system like [Strapi](https://strapi.io/) and [Prismic](https://prismic.io/) etc. 

Make no mistake, Pages is **not** a static site generator (SSG), Pages works in a _dynamic, lazy and smart_ way. A page is dynamically generated, cached and only re-generated when its content or layout has changed.

Pages takes your Markdown files, custom HTML or PHP code churns through layouts to create a page, then injects that back into your site.  Throughout that process, you can tweak how you want the page to look, what data gets displayed in the layout, and more.

Pages has a **flat-file bias**, and **doesn't require a database** to work. The content you’ll render on your site is generally written using Markdown, but you can use any kind of data source, for example you could use, a Joomla component, a database table, a headless CMS or even a webservice like [Airtable](https://airtable.com) or [Google Sheets](https://www.google.com/sheets/about/).

### Why Joomlatools Pages?

For the rebuild of [Joomlatools.com](http://joomlatools.test/blog/services/introducing-our-brand-new-website) we needed a solution that would allow us to easily include, and update, content on our site. Using Joomla articles and modules would make the site very hard to manage. Pages solves this in an elegant and simple way, not to mention it's super fast and easy to work with too!

### Who is Joomlatools Pages for?

Joomlatools Pages is for website developers and designers who create bespoke Joomla sites and have experience with html, markdown, etc. If you know how to use notepad you can work with Pages, knowledge of PHP is not required to get started.

## Requirements

* Joomla 3.6.5
* PHP7 
* Joomlatools Framework 3.4.3

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
