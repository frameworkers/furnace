# Furnace Internationalization README file
##
##

- All the files for a given locale should reside in a directory with the locale label. At a minimum,
the locale directory should have a subdirectory named 'strings'. This is where all localized string
files will exist.

- Default locale is specified in the application configuration file ([rootdir]/app/config/app.yml)
look for or add: default_locale: en-us (or other locale name)

- String messages generally take the format 'label:value' where 'label' is some unique identifier and
  'value' is the value to display on screen.
  
- Custom validation (error) messages are expected to be in the following location:
  
  [rootdir]/app/i18n/[locale-directory]/strings/
  
  in files named:
  
  model.[objectName].errors.[locale].yml
  
  an example:
  The custom, us-english messages for a BlogEntry class would be expected in the file:
  
  [rootdir]/app/i18n/en-us/strings/model.BlogEntry.errors.en-us.yml
    
    
        