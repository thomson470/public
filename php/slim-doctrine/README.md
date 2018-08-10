# Slim 3 with Doctrine 2

This is a fresh install of Akrabats [Slim 3 Skeleton App](https://github.com/akrabat/slim3-skeleton) with Doctrine 2.

You can use it for reference purposes while readying my post [Slim 3 with Doctrine 2](http://blog.sub85.com/slim-3-with-doctrine-2.html)



***) Register youphp/bin into %PATH% or $PATH
***) Create your database before run Create & Update scheme
  
### Create schema

    $ /path/to/slim-rest-api/vendor/bin/doctrine orm:schema-tool:create

# Windows	
	php vendor\bin\doctrine orm:schema-tool:create
	
    
### Update schema

    $ /path/to/slim-rest-api/vendor/bin/doctrine orm:schema-tool:update --force
	
# Windows
	php vendor\bin\doctrine orm:schema-tool:update --force
