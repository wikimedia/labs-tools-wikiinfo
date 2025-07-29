# WikiInfo

<https://wikiinfo.toolforge.org>

## Local development

1. `composer update`
2. `composer serve`

View <http://localhost:4000/>.

## Deployment on Toolforge

Initial setup, as per [Toolforge documentation](https://wikitech.wikimedia.org/wiki/Help:Toolforge/PHP):

1. Clone this repository.
   `git clone https://gerrit.wikimedia.org/r/labs/tools/wikiinfo`
2. Create public link, e.g.
   `$ ln -s ~/src/wikiinfo/public_html/ ~/public_html`
3. Start webservice
   `$ webservice --backend=kubernetes php8.2 restart`
4. Run composer (in a webservice shell, to ensure correct PHP version context)
   ```
   $ webservice shell
   shell$ cd wikiinfo
   shell:wikiinfo$ composer update --no-dev
   ```

Updates:

```
tools-login$ become wikiinfo

wikiinfo@tools-login$ webservice shell

shell$ cd src/wikiinfo/
shell:wikiinfo$ git pull
shell:wikiinfo$ composer update --no-dev
```
