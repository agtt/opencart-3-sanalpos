# OpenCart 3.x Sanal POS Entegrasyonu 

Aldığınız hataları http://www.karesoft.com.tr/opencart-3-x-sanalpos-entegrasyonu-ucretsiz bu başlık altında bildirebilirsiniz.

Veritabanında olması gereken tablo , normal kurulumda gelmez ise bunu phpmyadminden okutabilisiniz. Table Prefix kısmına dikkat edelim "oc_"
```
CREATE TABLE IF NOT EXISTS `oc_webposbank` (
`bank_id` INT(11) NOT NULL AUTO_INCREMENT,
`name` varchar(64) NOT NULL,
`image` varchar(64) NOT NULL,
`method` varchar(64) NOT NULL,
`model` varchar(64) NOT NULL,
`short` varchar(64) NOT NULL,
`status` tinyint(1) NOT NULL,
PRIMARY KEY (`bank_id`)
) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci;
```