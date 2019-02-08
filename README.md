To INSTALL, simply copy the amazon.php and amazon-config.php to your plugins directory. 

In addition, you'll need to set up your DB parameters in the amazon-config.php file.

You can use the following SQL code to create the appropriate table structure:

```
--
-- Table structure for table `AmazonProducts`
--

CREATE TABLE `AmazonProducts` (
  `prod_id` varchar(12) NOT NULL,
  `title` varchar(128) NOT NULL,
  `price` float NOT NULL,
  `link` varchar(2083) NOT NULL,
  `image` varchar(300) NOT NULL,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for table `AmazonProducts`
--
ALTER TABLE `AmazonProducts`
  ADD PRIMARY KEY (`prod_id`);
COMMIT;
```

To see how to use the plugin within your pages, see the "example.md" file included. 

Tags of the form: [[ AZLINK 0316359483 ]] are replaced with the product meta data (in this case, an associate link to the product). 
The number is the ASIN for the product. 
Other meta tags that may be used as well such as AZIMG or AZTITLE. Price is of the form [[ AZ 0316359483 ]], and must always be included for the code to know to look for the product at all. 
