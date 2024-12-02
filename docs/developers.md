
# Mautic wordpress integration plugin for newsletters
   
## Development:   
  
If you would like to contribute, these are the steps for running the site locally on your device:  
     
1. git checkout main and branch off from there      
```
git checkout main  
git checkout -b feature-NAME  
```


2. update backend dependencies via composer.  
This will install backend deps (mautic api etc).
```bash
cd ./backend && composer install
```

  
3. Install frontend deps.  
Install dependencies needed for public assets:  
``` 
npm install  
```  
  
4. Compile frontend dependencies:  
- `npm run start` - combine/sass/minify/babel - etc.  typical gulp-ified frontend processing. 
- `npm run gen-dist` - generate a distributable plugin bundle

## Build and deploy a documentation site (www.maltyst.com):  
Site is hosted on github pages and is using [jekyll](https://jekyllrb.com).   
[jekyll](https://jekyllrb.com) is a static site generation written in ruby, this is just what github pages uses.    
Pushing main branch to github will deploy the maltyst.com 


## Todo  

- make `double-optin` optional (some smtp providers like mxroute won't like it )   
- **mjml** parsing at the email dispatch time. So going away with pre-compilation.
  This  way we can make mjml modifiable and expose in admin. Can probably solve this by implementing **mjml** api instead and requiring use to provide mjml token.   
- perhaps add optin captcha? (why with double optin?)     
- perhaps add optin throttling?     