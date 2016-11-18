# Test suite

Various HTML, PHP, and SSI files for checking real-world performance of web hosts

Upload these files to a web host, and then test the speed of your host using [GTMetrix](https://gtmetrix.com), [WebPageTest](http://www.webpagetest.org), or plain old [Apache Bench](https://httpd.apache.org/docs/2.2/programs/ab.html).

The goal is to have each file with the same content, while delivering that content slightly differently so you can see differences in web host performance, although you could also use it to check differences in delivery methods.

In addition to having three different delivery methods, there are also three different coding techniques employed.

**Multiple local**: With these files, each CSS and JS resource is called individually, and served from the same server that hosts the files. Virtually all websites used to be built this way, once upon a time.

**Combined local**: With these files, CSS and JS resources are combined and minified, so there is only one CSS call and only one JS call per page. The CSS and JS resources are served from the same server that hosts the files. This is the new way of building sites that require CSS and JS resources, where the trade-off for the larger file size is the reduction in the number of HTTP requests.

**Content Delivery Network (CDN)**: With these files, the CSS and JS resources are called individually from CDN sources external to the web host being tested. This technique off-loads some of the duties for serving the site to computers around the globe, the hope being that between positioning the resources closer to the end user and browser caching, there will be an overall reduction in page-load times, despite the increased number of HTTP requests.

In each case, the CSS and JS resources are for a [Bootstrap](http://getbootstrap.com) site that also uses [Font Awesome](http://fontawesome.io), which means of course that it loads JS both for Bootstrap and the prerequisite [jQuery](http://jquery.com).

One other aspect of web hosting you should be able to see with these files is the difference between an HTTP/1.x host and an HTTP/2 host, because HTTP/2 was designed to allow multiple resources to be downloaded to the browser at once. If it does (and you&rsquo;re using an HTTP/2 hosting service), then you will be able to save yourself a ton of time and aggravation by not having to combine your CSS and JS files.

**Recommended use**

1. Upload the &ldquo;test-suite&rdquo; folder to the server you wish to test.
2. Point your testing software at each of the test files of interest. If you have no plans to create an SSI/SHTML website on a particular server, for example, there is no need to run the tests.
