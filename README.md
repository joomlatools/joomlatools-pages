![Build package](https://github.com/joomlatools/joomlatools-pages/workflows/Build%20package/badge.svg?branch=master)

# Joomlatools Pages

### What is Joomlatools Pages?

***Joomlatools Pages is a very fast [**page generator**](https://github.com/joomlatools/joomlatools-pages/discussions/655) and flexible framework that makes building websites with any CMS, API, or database [fun again](https://github.com/joomlatools/joomlatools-pages/wiki/Developer-Joy). It can be installed in Joomla as a component, or be used standalone.*** 

It's written in PHP and inspired by the ideas and concepts of flat-file CMS systems like [Grav](https://getgrav.org/) and [Statamic](statamic.com), static site generators like [Jekyll]( https://jekyllrb.com), [Hugo](https://gohugo.io), [Gatsby](https://www.gatsbyjs.org/) and Headless CMS system like [Strapi](https://strapi.io/) and [Prismic](https://prismic.io/) etc. 

Make no mistake, Pages is **not** a static site generator (SSG), Pages works in a _dynamic, lazy and smart_ way. Pages are dynamically generated, and cached and incrementally re-generated when their content or layout has changed.

> Pages is more than a page generator, _it's an engine for creating websites_, combining the power of a dynamic web application, with the performance of a static site generator. 

Pages takes your custom HTML/CSS/JS and churns through layouts to create a page. Throughout that process, you can tweak how you want the page to look, what data gets displayed in the layout, all that is required is a little bit of PHP logic to bring it all together.

Pages is **datasource agnostic**, your data can come from anywhere, for example you could use, a Joomla component, a database table, a headless CMS or even a webservice like [Airtable](https://airtable.com) or [Google Sheets](https://www.google.com/sheets/about/).

Pages can both be installed in Joomla and be used standalone, it's 100% compatible with every Joomla template, and also offers it's own a very powerful [theming system](https://github.com/joomlatools/joomlatools-pages/wiki/Themes). 

We built it to be your perfect buddy, and [bring joy to web development](https://github.com/joomlatools/joomlatools-pages/wiki/Developer-Joy), it doesn't get in your way and it's always there if you need it.

### Why Joomlatools Pages?

For the rebuild of [Joomlatools.com](http://joomlatools.com/blog/services/introducing-our-brand-new-website) we needed a solution that would allow us to easily include, and update, content on our site. Using Joomla would make the site very hard to manage. Pages solves this in an elegant and simple way, not to mention it's super fast and easy to work with too!

### Who is Joomlatools Pages for?

Joomlatools Pages is for website developers and designers who create bespoke (Joomla) sites and have experience with html, markdown, etc. If you know how to use notepad you can work with Pages, knowledge of PHP is not required to get started.

## Requirements

* PHP8.0
* Joomlatools Framework 4.0

## Installation

### In Joomla 

Go to the root directory of your installation in command line and execute this command:

```
composer require joomlatools/pages --ignore-platform-reqs
```

When installing in Joomla you need to use the --ignore-platform-reqs flag if not the installation will fail due to a mismatch in the platform
constraint. Joomla's requires PHP 5.3.10 as minimum while Joomlatools Pages is set to minimum PHP7.4

### Standalone

Go to the root directory of your installation in command line and execute this command:

```
composer require joomlatools/pages
```
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
* Read the [Joomlatools Developer Blog](https://www.joomlatools.com/developer/blog/)
* Subscribe to the [Joomlatools Developer Newsletter](https://www.joomlatools.com/developer/newsletter/)
