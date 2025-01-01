# Mautic wordpress integration plugin for newsletters

## Status:
Note  - this should be looked at as a still early project. Use with caution.
<span style="color:red;">DO not try this yet. im still working on pushing it out. dec 2nd 2024</span>

## Purpose:
Provide wordpress integration with mautic instance. Goal is to make all or majority of consumer interactions to be be done through wordpress site 
(using mautic api under the hood).  

This may be more preferred workflow for some people that would prefer not to expose mautic instance and have relatively simple requirenments.
    
Working with Wordpress is the last thing I want to do on this earth, so buy me a beer god damn it.  
  

## Features and highlights:
  
- **double optin functionality**  
User gets confirmation email and has to click to confirm enrollment in order to be added onto specific mautic segments. Confirmation url is on the wordress site.  
Note - for double optin transaction emails - plugin will use whatever smtp/mail server was configured in your wordpress instance.  
  
- **throwaway emails detection and block**  
Emails from throwaway services like mailinator are not allowed.  This is not bulletprof and relies on third party library.
  
- **wordpress integrated preference center**  
  Preference center is also integrated into wordpress site.
    
- **wordpress integrated unsubscribe page** (same as preference center)  
  Preference center is also integrated into wordpress site.

- **"new posts" notifications**  
  You can designate a mautic segment, ex: "newposts" to receive new posts notifications once new posts change status from unpublished to published.

- **async fetch frontend calls** 
  for improved UX and not delay a page render.

- subscribers added to mautic via api, never exposing mautic instance location.


## How to use:
  
1) Obviously install this wordpress plugin.   

2) add new custom unique identifier field to your mautic instance.  

Why? because while mautic assigns some unique id - it is numeric and simply autoincrements for new contacts.      
Which means if used in urls for unique identification - we are opening ourselves to easy contact enumeration vulnerability.  
  
Which is why - let's add a new unique file, that will then be used for a unique consumer identification and can be used in emails etc:    
a. Go to: "mautic" -> settings -> "Custom fields".   
b. Click on "New"   
c. Enter something like this:   

```
Label:  maltyst_contact_uqid

Alias:  maltyst_contact_uqid
Object: contact 
Group:  core
Data Type: Text Short answer
Is unique identifier: Yes
Indexable: Yes
Visible on forms: No
Available for use: Yes
Visible on quick add : Yes
Publicly updatable : No
Required: No
```

Note: If you have a fresh mautic instance and only intend to use it for wordpress integration - 
you could check it as `Required: Yes`.  But if you have other usecases for mautic where contacts are coming
from other source that wont have a `maltyst_contact_uqid` - then use `Required: No`.  
  
Note: If you also going to be adding contacts manually - you should probably set "Visible on quick add : Yes".

Note: wordpress plugin will generate a unique token for each new subscriber.     
However - if you already have subscribers in mautic - you will have to generate unique tokens for them manually.    
it is trivial to write some script that would iterate over mautic `leads` table and generates some uuidv7 for those contacts.  


3) Create wordpress page for preference-center && unsubscribe, for example: `/preference-center`  
And embed this shortcode:  `[maltyst_preference_center pc="pc-somename"]`

4) Create wordpress page for double optin confirmation, ex: `/email-optin-result`
And embed this shortcode on there:  `[maltyst_optin_confirmation optin="optin-somename"]`

5) Add optin form shortcode to sidebar or footer or wherever you like:
`[maltyst_optin_form id="optin-somename"]`

6) Create mautic segments you want to use.
    Note - preference center will only display segments explicitely marked to be displayed in preference center.

7) Create following emails in mautic with with whatever information you want users to see on initial email and on comfirmation email after user cliks on confirmation link. You have to use **replacement url formats** instead of mautic-provided tokens so users are directed to wordpress site instead:

* double-optin
* welcome

^ you can change template names in settings.




## Replacement URL Formats:
Note - as the goal is not to direct user to mautic instance, you shouldn't use tokens provided by mautic for **unsubscribe**, **preference center** or **double optin**. Rather use these instead:

Preference center:
```html
<a href="https://example.com/preference-center?maltyst_contact_uqid={contactfield=maltyst_contact_uqid}">Preference Center
</a>
```

Unsubscribe:
```html
<a href="https:/example.com/preference-center?maltyst_contact_uqid={contactfield=maltyst_contact_uqid}&unsubscribe-from-all=true">Unsubscribe
</a>
```

Double optin:
```html
<a href="{confirmation_url}={confirmation_token}">Let's do this</a>
```



## Example mautic segments:
  *  newposts  - new posts notifications
  *  marketing - whatever 
  *  recurring