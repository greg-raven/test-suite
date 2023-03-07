# Test suite

Various HTML, PHP, and SSI files for checking real-world performance of web hosts

Upload these files to a web host, and then test the speed of your host using [GTMetrix](https://gtmetrix.com), [WebPageTest](https://www.webpagetest.org), or plain old [Apache Bench](https://httpd.apache.org/docs/2.2/programs/ab.html).

The goal is to have each file with the same content, while delivering that content slightly differently so you can see differences in web host performance, although you could also use it to check differences in delivery methods.

In addition to having three different delivery methods, there are also three different coding techniques employed.

**Multiple local**: With these files, each CSS and JS resource is called individually, and served from the same server that hosts the files. Virtually all websites used to be built this way, once upon a time.

**Combined local**: With these files, CSS and JS resources are combined and minified, so there is only one CSS call and only one JS call per page. The CSS and JS resources are served from the same server that hosts the files. This is the new way of building sites that require CSS and JS resources, where the trade-off for the larger file size is the reduction in the number of HTTP requests.

**Content Delivery Network (CDN)**: With these files, the CSS and JS resources are called individually from CDN sources external to the web host being tested. This technique off-loads some of the duties for serving the site to computers around the globe, the hope being that between positioning the resources closer to the end user and browser caching, there will be an overall reduction in page-load times, despite the increased number of HTTP requests.

**Baseline**: These are three HTML files with identical content and construction, but with different filename extensions (HTML, PHP, and SHTML). These will reveal any inherent hosting-speed differences in your server.

In each case, the CSS and JS resources are for a [Bootstrap](https://getbootstrap.com) site, which means of course that it loads JS both for Bootstrap and the prerequisite [jQuery](https://jquery.com).

One other aspect of web hosting you should be able to see with these files is the difference between an HTTP/1.x host and an HTTP/2 host, because HTTP/2 was designed to allow multiple resources to be downloaded to the browser at once. If it does (and you&rsquo;re using an HTTP/2 hosting service), then you will be able to save yourself a ton of time and aggravation by not having to combine your CSS and JS files.

**Recommended use**

1. Upload the &ldquo;test-suite&rdquo; folder to the server you wish to test.
2. Point your testing software at each of the test files of interest. If you have no plans to create an SSI/SHTML website on a particular server, for example, there is no need to run the tests.

---

**Version history**

+ January 7, 2023 &mdash; added link to the Kitten Therapy video
+ January 7, 2023 &mdash; fixed some minor coding omissions
+ January 6, 2023 &mdash; deployed these files to [InfinityFree.net](http://test-suite.infinityfreeapp.com) and [NearlyFreeSpeech.net](https://test-suite.nfshost.com)
+ August 26, 2022 &mdash; update to jQuery 3.6.1
+ January 31, 2022 &mdash; Switched to FreeWebHostingArea hosting
+ June 31, 2021 &mdash; Added &ldquo;baseline&rdquo; file set
+ March 2, 2021 &mdash; update to jQuery 3.6.0
+ February 13, 2019 &mdash; update to Bootstrap 3.4.1
+ December 6, 2016 &mdash; update to jQuery 3.1.1, Bootstrap 3.3.7.
+ January 3, 2016 &mdash; created
