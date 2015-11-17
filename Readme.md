# SmartyCache

A simple module to cache some parts of a template. It uses a smarty function.

## Usage

The example will cache the generate code of a navigation menu. It will generate different caches depending of the
current category, the lang and the currency. The cache will be used for 60 seconds, before being deleted :

```smarty
{tcache key="navigation" ttl="60" category={category attr="id"}}
    <div class="navigation">
        {loop name="category-navigation" type="category" }
            ....
            <!-- other expensive loop -->
            ....
        {/loop}
    </div>
{/tcache}
```

## Parameters

- **key** : (mandatory) a unique key
- **ttl** : (mandatory) a time to live in seconds
- **lang** : specific cache by lang, possible values : *on*, *off* (default: *on*)
- **currency** : specific cache by currency, possible values : *on*, *off* (default: *on*) 
- **customer** : specific cache by customer, possible values : *on*, *off* (default: **off**) 
- **admin** : specific cache by administator, possible values : *on*, *off* (default: *off*) 
- **role** : specific cache by role (none, customer, admin), possible values : *on*, *off* (default: *off*) 

You can add as many arguments as you need. These arguments will be used to generate a unique key.  

