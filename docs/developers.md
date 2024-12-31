
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

## Known hacks   

These are issues that I would like to fix but they are limitations on the mautic side.   

- **newpost template - mjml rendering**   
right now mjml rendering has to happen outside of mautic which is PITA.  
Because there is no way to create email as mjml in mautic via api:    
https://forum.mautic.org/t/allow-email-created-via-api-to-be-mjml/22257  
And Even if you create an mjml segment email in mautic manually - mautic "send to segment" api does not support passing tokens.    
see:  https://developer.mautic.org/#send-email-to-segment    
Which means we must render mjml to thml here so someone needs to maintain mjml server - which is annoying. ☹️☹️☹️  


- **one click unsubscribe headers**     
  While we can use custom unsubscribe links in email body - mautic stil injects a custom mautic one click unsubscribe link.    
  Which is heavy handed solution and sets email to DNC (do not contanct)s.     
  I would prefer to just use my own custom links, but this does not seem to be possible.  
  See details: 
    a) custom header saving bug: https://github.com/mautic/mautic/issues/13387   
    b) forum issue about DNC:    https://forum.mautic.org/t/one-click-unsubscribe-header-full-do-not-contact-is-excessive/34560  
        need to implement the issue: if `List-Unsubscribe` is present - dont add mautic option.   