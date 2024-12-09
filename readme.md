
# Maltyst - mautic wordpress integration plugin (unofficial)

## End User Documentation:
see https://www.maltyst.com, or read ./docs/_site markdown files for jekyll.  


## Development:
- git checkout and branch off  
- update backend dependencies via composer. This will install backend deps (mautic api etc).  
```sh
cd ./backend && composer install
```
- install frontend dependencies
```sh
cd ./frontend
npm install
```
- run a frontend build.
```sh
npm run watch
```

This will compile .mjs and scss code, generate .br and gz files and generate a plugin distribution
in `./plugin-dist/maltyst.zip`.
Watch command will keep regenerating `maltyst.zip` package on any changes to .js or .scss.


## Generate plugin version for production:
Run thi command to generate a distributable plugin bundle
```sh
npm run build
```


for me: 
get zip file: sftp://hs-dev1/mnt/480g_drive/projects/maltyst/plugin-dist
install into test wp, test.

  
## Development Todo    
- make `double-optin` optional   
- **mjml** parsing at the email dispatch time. So going away with pre-compilation.
  This  way we can make mjml modifiable and expose in admin. Can probably solve this by implementing **mjml** api instead and requiring use to provide mjml token. 
- perhaps add optin captcha  
- perhaps add optin throttling  