<!-- PROJECT LOGO -->
<br />
<div align="center">
  <a href="https://github.com/figuren-theater/ft-data">
    <img src="https://raw.githubusercontent.com/figuren-theater/logos/main/favicon.png" alt="figuren.theater Logo" width="100" height="100">
  </a>

  <h1 align="center">figuren.theater | Data</h1>

  <p align="center">
    Data structures, posttypes & taxonomies together with the tools to handle this data for a WordPress Multisite like <a href="https://figuren.theater">figuren.theater</a>.
    <br /><br /><br />
    <a href="https://meta.figuren.theater/blog"><strong>Read our blog</strong></a>
    <br />
    <br />
    <a href="https://figuren.theater">See the network in action</a>
    •
    <a href="https://mein.figuren.theater">Join the network</a>
    •
    <a href="https://websites.fuer.figuren.theater">Create your own network</a>
  </p>
</div>

## About


This is the long desc

* [x] *list closed tracking-issues or `docs` files here*
* [ ] Do you have any [ideas](/issues/new) ?

## Background & Motivation

...

## Install

1. Add this repository to your `composer.json`
```json
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/figuren-theater/ft-data"
    }
],
"extra": {
    "dropin-paths": {
        "htdocs/vendor/rss-bridge/rss-bridge": [
            "package:figuren-theater/ft-data:templates/htdocs/vendor/rss-bridge/rss-bridge/whitelist.txt",
            "package:figuren-theater/ft-data:templates/htdocs/vendor/rss-bridge/rss-bridge/config.ini.php"
        ]
    }
}
```

2. Install via command line
```sh
composer require figuren-theater/ft-data
```

## Usage


### Plugins included

This package contains the following plugins.
Thoose are completely managed by code and lack of their typical UI.

* [Distributor](https://github.com/10up/distributor)
    Distributor is a WordPress plugin that makes it easy to syndicate and reuse content ...
* [Distributor - Remote Quickedit](https://wordpress.org/plugins/distributor-remote-quickedit)
    Re-enable quickedit for distributed posts on the receiving site. This allows to make changes to the original post from the remote site. Add-on for the glorious distributor-plugin by 10up.
* [Utility Taxonomy](https://github.com/humanmade/hm-utility-taxonomy)
    A hidden taxonomy, used for filtering of posts/pages etc. in a way that is more performant than using the likes of post meta.
* [Shadow Taxonomy](https://github.com/carstingaxion/shadow-taxonomy)
    Provides a Simple API for making Many To Many Relationships in WordPress.
* [Extended CPTs](https://github.com/johnbillion/extended-cpts)
    A library which provides extended functionality to WordPress custom post types and taxonomies.
* [Term Management Tools](https://wordpress.org/plugins/term-management-tools/#developers)
    Allows you to merge terms, move terms between taxonomies, and set term parents, individually or in bulk.

* [figuren-theater/ft-network-sourcelinks](https://github.com/figuren-theater/ft-network-sourcelinks)
    Manage external Links as 'other' personal profiles or external sources. Handles syncing content from thoose sites, (NOT YET: using RSS-Bridge, friends,) and the old native WordPress Link-Manager a little modified.

* [RSS-Bridge](/docs/inc/rss-bridge/README.md)

* [feed-pull](/docs/inc/feed-pull/README.md)
    Pull feeds into WordPress

## Built with & uses

  - [dependabot](/.github/dependabot.yml)
  - ....

## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request


## Versioning

We use [Semantic Versioning](http://semver.org/) for versioning. For the versions
available, see the [tags on this repository](/tags).

## Authors

  - **Carsten Bach** - *Provided idea & code* - [figuren.theater/crew](https://figuren.theater/crew/)

See also the list of [contributors](/contributors)
who participated in this project.

## License

This project is licensed under the **GPL-3.0-or-later**, see the [LICENSE](/LICENSE) file for
details

## Acknowledgments

  - [altis](https://github.com/search?q=org%3Ahumanmade+altis) by humanmade, as our digital role model and inspiration
  - [@roborourke](https://github.com/roborourke) for his clear & understandable [coding guidelines](https://docs.altis-dxp.com/guides/code-review/standards/)

