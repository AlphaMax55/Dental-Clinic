-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Anamakine: localhost:3306
-- Üretim Zamanı: 25 Eyl 2026, 01:10:58
-- Sunucu sürümü: 8.0.46
-- PHP Sürümü: 8.4.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `drib4094_dis_klinik`
--

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `anasayfa_icerik`
--

CREATE TABLE `anasayfa_icerik` (
  `id` int NOT NULL,
  `tedaviler_baslik_tr` varchar(255) DEFAULT NULL,
  `tedaviler_baslik_en` varchar(255) DEFAULT NULL,
  `tedaviler_alt_baslik_tr` varchar(255) DEFAULT NULL,
  `tedaviler_alt_baslik_en` varchar(255) DEFAULT NULL,
  `sol_sutun_baslik_tr` varchar(255) DEFAULT NULL,
  `sol_sutun_baslik_en` varchar(255) DEFAULT NULL,
  `sag_sutun_baslik_tr` varchar(255) DEFAULT NULL,
  `sag_sutun_baslik_en` varchar(255) DEFAULT NULL,
  `akademik_vizyon_baslik_tr` varchar(100) DEFAULT NULL,
  `akademik_vizyon_baslik_en` varchar(100) DEFAULT NULL,
  `randevu_baslik_tr` varchar(100) DEFAULT NULL,
  `randevu_baslik_en` varchar(100) DEFAULT NULL,
  `randevu_buton_yazi_tr` varchar(100) DEFAULT NULL,
  `randevu_buton_yazi_en` varchar(100) DEFAULT NULL,
  `doktor_aciklama_tr` longtext,
  `doktor_aciklama_en` longtext,
  `video_baslik_tr` varchar(255) DEFAULT 'Kliniğimizi Tanıyın',
  `video_baslik_en` varchar(255) DEFAULT 'Get to Know Our Clinic',
  `video_alt_baslik_tr` varchar(255) DEFAULT 'Prof. Dr. İbrahim Duran ve ekibinin çalışma felsefesini, kliniğimizin teknolojik altyapısını yakından keşfedin.',
  `video_alt_baslik_en` varchar(255) DEFAULT 'Discover Prof. Dr. İbrahim Duran and his team''s work philosophy and our clinic''s technological infrastructure.',
  `video_url` varchar(500) DEFAULT 'https://www.youtube.com/embed/VIDEO_ID',
  `video_aktif` tinyint(1) DEFAULT '1',
  `sss_baslik_tr` varchar(100) DEFAULT NULL,
  `sss_baslik_en` varchar(100) DEFAULT NULL,
  `sss_alt_baslik_tr` varchar(255) DEFAULT NULL,
  `sss_alt_baslik_en` varchar(255) DEFAULT NULL,
  `tedaviler_metin_json` longtext,
  `seo_title_tr` varchar(255) DEFAULT NULL,
  `seo_title_en` varchar(255) DEFAULT NULL,
  `seo_description_tr` text,
  `seo_description_en` text,
  `seo_keywords_tr` varchar(255) DEFAULT NULL,
  `seo_keywords_en` varchar(255) DEFAULT NULL,
  `seo_og_image` varchar(255) DEFAULT NULL,
  `seo_canonical` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `slider_ids` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `anasayfa_slider`
--

CREATE TABLE `anasayfa_slider` (
  `id` int NOT NULL,
  `title_tr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `title_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `subtitle_tr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `subtitle_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `badge_tr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `badge_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `features_tr` text COLLATE utf8mb4_unicode_ci,
  `features_en` text COLLATE utf8mb4_unicode_ci,
  `sira` int DEFAULT '0',
  `aktif` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ayarlar`
--

CREATE TABLE `ayarlar` (
  `id` int NOT NULL,
  `anahtar` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `deger` text COLLATE utf8mb4_general_ci,
  `aciklama` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blog_aboneler`
--

CREATE TABLE `blog_aboneler` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blog_kategoriler`
--

CREATE TABLE `blog_kategoriler` (
  `id` int NOT NULL,
  `kategori_adi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ikon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'FileText',
  `sira` int DEFAULT '0',
  `durum` tinyint(1) DEFAULT '1',
  `silindi` tinyint(1) DEFAULT '0',
  `silinme_tarihi` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blog_seo_ayarlar`
--

CREATE TABLE `blog_seo_ayarlar` (
  `id` int NOT NULL,
  `anahtar` varchar(100) NOT NULL,
  `deger` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blog_sss`
--

CREATE TABLE `blog_sss` (
  `id` int NOT NULL,
  `soru` varchar(500) NOT NULL,
  `cevap` text NOT NULL,
  `sira` int DEFAULT '0',
  `durum` tinyint(1) DEFAULT '1',
  `silindi` tinyint(1) DEFAULT '0',
  `silinme_tarihi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blog_yazilar`
--

CREATE TABLE `blog_yazilar` (
  `id` int NOT NULL,
  `baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ozet` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `icerik` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `resim` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `yazar` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Prof. Dr. İbrahim Duran',
  `yazar_unvan` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `goruntulenme` int DEFAULT '0',
  `begeni` int DEFAULT '0',
  `yorum_sayisi` int DEFAULT '0',
  `durum` tinyint(1) DEFAULT '1',
  `silindi` tinyint(1) DEFAULT '0',
  `silinme_tarihi` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `seo_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seo_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `seo_keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seo_title_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seo_description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `seo_keywords_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `blog_yazilar_silinmis_backup`
--

CREATE TABLE `blog_yazilar_silinmis_backup` (
  `id` int NOT NULL DEFAULT '0',
  `baslik` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `kategori_slug` varchar(100) DEFAULT NULL,
  `ozet` text,
  `icerik` longtext NOT NULL,
  `resim` varchar(500) DEFAULT NULL,
  `yazar` varchar(100) DEFAULT 'Prof. Dr. İbrahim Duran',
  `yazar_unvan` varchar(200) DEFAULT 'Ağız, Diş ve Çene Cerrahisi Uzmanı',
  `goruntulenme` int DEFAULT '0',
  `begeni` int DEFAULT '0',
  `yorum_sayisi` int DEFAULT '0',
  `durum` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `silindi` tinyint(1) DEFAULT '0',
  `silinme_tarihi` datetime DEFAULT NULL,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `seo_title_en` varchar(255) DEFAULT NULL,
  `seo_description_en` text,
  `seo_keywords_en` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `commands`
--

CREATE TABLE `commands` (
  `id` int NOT NULL,
  `command` varchar(50) NOT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `dinamik_sayfalar`
--

CREATE TABLE `dinamik_sayfalar` (
  `id` int NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `baslik` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `menu_adi` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `aktif` tinyint(1) DEFAULT '1',
  `sira` int DEFAULT '0',
  `template` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'default',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `dinamik_sayfa_bloklari`
--

CREATE TABLE `dinamik_sayfa_bloklari` (
  `id` int NOT NULL,
  `sayfa_id` int NOT NULL,
  `blok_tip` enum('hero','text','image','gallery','features','cta','accordion','contact') COLLATE utf8mb4_general_ci DEFAULT 'text',
  `baslik` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `aciklama` text COLLATE utf8mb4_general_ci,
  `resim` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `icon` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `icerik_json` text COLLATE utf8mb4_general_ci,
  `sira` int DEFAULT '0',
  `aktif` tinyint(1) DEFAULT '1',
  `css_class` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `dosya_yollari`
--

CREATE TABLE `dosya_yollari` (
  `id` int NOT NULL,
  `anahtar` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `yol` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `aciklama` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guncelleyen` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `galeri`
--

CREATE TABLE `galeri` (
  `id` int NOT NULL,
  `baslik` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `kategori` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `thumbnail` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `likes` int DEFAULT '0',
  `sira` int DEFAULT '0',
  `aktif` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `galeri_ayarlar`
--

CREATE TABLE `galeri_ayarlar` (
  `id` int NOT NULL,
  `anahtar` varchar(100) NOT NULL,
  `deger` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `galeri_kategoriler`
--

CREATE TABLE `galeri_kategoriler` (
  `id` int NOT NULL,
  `kategori_adi` varchar(100) NOT NULL,
  `kategori_slug` varchar(100) NOT NULL,
  `ikon` varchar(50) DEFAULT 'Camera',
  `medya_tipi` enum('resim','video','hepsi') DEFAULT 'hepsi',
  `sira` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `galeri_resimler`
--

CREATE TABLE `galeri_resimler` (
  `id` int NOT NULL,
  `baslik` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `aciklama` text,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `seo_og_image` varchar(500) DEFAULT NULL,
  `kategori_id` int NOT NULL,
  `medya_tipi` enum('resim','video') DEFAULT 'resim',
  `resim_url` varchar(500) NOT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_sure` varchar(20) DEFAULT NULL COMMENT 'ISO 8601 duration (ör: PT1M35S)',
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `tarih` varchar(20) DEFAULT NULL,
  `begeni` int DEFAULT '0',
  `goruntulenme` int DEFAULT '0',
  `durum` tinyint(1) DEFAULT '1',
  `sira` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `silindi` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `galeri_resimler_yedek_20260922`
--

CREATE TABLE `galeri_resimler_yedek_20260922` (
  `id` int NOT NULL DEFAULT '0',
  `baslik` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `aciklama` text,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text,
  `seo_keywords` varchar(255) DEFAULT NULL,
  `seo_og_image` varchar(500) DEFAULT NULL,
  `kategori_id` int NOT NULL,
  `medya_tipi` enum('resim','video') DEFAULT 'resim',
  `resim_url` varchar(500) NOT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_sure` varchar(20) DEFAULT NULL COMMENT 'ISO 8601 duration (ör: PT1M35S)',
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `tarih` varchar(20) DEFAULT NULL,
  `begeni` int DEFAULT '0',
  `goruntulenme` int DEFAULT '0',
  `durum` tinyint(1) DEFAULT '1',
  `sira` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `silindi` tinyint(1) DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `iletisim_mesajlari`
--

CREATE TABLE `iletisim_mesajlari` (
  `id` int NOT NULL,
  `ad_soyad` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `telefon` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `konu` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mesaj` text COLLATE utf8mb4_general_ci NOT NULL,
  `durum` enum('okunmadi','okundu','cevaplandi') COLLATE utf8mb4_general_ci DEFAULT 'okunmadi',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `silinme_tarihi` timestamp NULL DEFAULT NULL,
  `silindi` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `iletisim_seo_ayarlar`
--

CREATE TABLE `iletisim_seo_ayarlar` (
  `id` int NOT NULL,
  `anahtar` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deger` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kullanicilar`
--

CREATE TABLE `kullanicilar` (
  `id` int NOT NULL,
  `kullanici_adi` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sifre` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ad_soyad` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `rol` enum('superadmin','admin','editor') COLLATE utf8mb4_general_ci DEFAULT 'editor',
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `son_giris` timestamp NULL DEFAULT NULL,
  `durum` tinyint DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `kurumsal_ayarlari`
--

CREATE TABLE `kurumsal_ayarlari` (
  `id` int NOT NULL,
  `ayar_key` varchar(100) NOT NULL,
  `ayar_value` text NOT NULL,
  `ayar_tip` enum('text','textarea','json','image') DEFAULT 'text',
  `label` varchar(255) DEFAULT NULL,
  `sira` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `page_slider`
--

CREATE TABLE `page_slider` (
  `id` int NOT NULL,
  `page_slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sayfa slug: anasayfa, kurumsal, galeri, teknolojiler, blog, kvkk, tedaviler, tedavi-detay, iletisim',
  `title_tr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle_tr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subtitle_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_tr` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `badge_en` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `features_tr` text COLLATE utf8mb4_unicode_ci COMMENT 'virgülle ayrılmış özellikler',
  `features_en` text COLLATE utf8mb4_unicode_ci,
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `button_text_tr` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'İletişime Geç',
  `button_text_en` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Contact Us',
  `button_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '/iletisim/',
  `sira` int DEFAULT '0',
  `aktif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `randevular`
--

CREATE TABLE `randevular` (
  `id` int NOT NULL,
  `ad_soyad` varchar(100) COLLATE utf8mb4_turkish_ci NOT NULL,
  `telefon` varchar(20) COLLATE utf8mb4_turkish_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `cinsiyet` varchar(10) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `dogum_tarihi` date DEFAULT NULL,
  `tc_no` varchar(11) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `hasta_tipi` varchar(20) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `konu` varchar(200) COLLATE utf8mb4_turkish_ci DEFAULT NULL,
  `mesaj` text COLLATE utf8mb4_turkish_ci,
  `randevu_tarihi` date DEFAULT NULL,
  `randevu_saati` time DEFAULT NULL,
  `not` text COLLATE utf8mb4_turkish_ci,
  `durum` enum('bekliyor','onaylandi','iptal') COLLATE utf8mb4_turkish_ci DEFAULT 'bekliyor',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `ilac_kullaniyor` tinyint(1) DEFAULT '0',
  `ilac_detay` text COLLATE utf8mb4_turkish_ci,
  `alerji_var` tinyint(1) DEFAULT '0',
  `alerji_detay` text COLLATE utf8mb4_turkish_ci,
  `hamile` tinyint(1) DEFAULT '0',
  `diyabet` tinyint(1) DEFAULT '0',
  `kalp_hastaligi` tinyint(1) DEFAULT '0',
  `kan_sulandirici` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `rol_yetkileri`
--

CREATE TABLE `rol_yetkileri` (
  `id` int NOT NULL,
  `rol` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `modul` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `yetki` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `deger` tinyint DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_ayarlari`
--

CREATE TABLE `site_ayarlari` (
  `id` int NOT NULL,
  `ayar_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ayar_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `ayar_tip` enum('text','textarea','number','boolean','json','image','url') COLLATE utf8mb4_unicode_ci DEFAULT 'text',
  `grup` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'genel',
  `label` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aciklama` text COLLATE utf8mb4_unicode_ci,
  `sira` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_cevirileri`
--

CREATE TABLE `site_cevirileri` (
  `id` int NOT NULL,
  `metin_hash` varchar(32) NOT NULL,
  `turkce_metin` text NOT NULL,
  `ingilizce_metin` text NOT NULL,
  `kayit_tarihi` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `site_istatistikler`
--

CREATE TABLE `site_istatistikler` (
  `id` int NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `sayfa` varchar(500) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `oturum_id` varchar(100) DEFAULT NULL,
  `user_agent` text,
  `referer` text,
  `tarih` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT NULL,
  `ulke` varchar(100) DEFAULT NULL,
  `sehir` varchar(100) DEFAULT NULL,
  `ilce` varchar(100) DEFAULT NULL,
  `yayin_adi` varchar(100) DEFAULT NULL,
  `is_bot` tinyint(1) DEFAULT '0',
  `bot_tipi` varchar(50) DEFAULT NULL,
  `ai_kaynak` varchar(100) DEFAULT NULL,
  `sayfa_sayisi` int DEFAULT '1',
  `oturum_suresi` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `sss`
--

CREATE TABLE `sss` (
  `id` int NOT NULL,
  `soru` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `cevap` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Genel',
  `sira` int DEFAULT '0',
  `aktif` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tedaviler`
--

CREATE TABLE `tedaviler` (
  `id` int NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `baslik` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `breadcrumb` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Tedaviler',
  `kategori` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `meta_description` text COLLATE utf8mb4_general_ci,
  `seo_title_tr` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seo_title_en` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seo_description_tr` text COLLATE utf8mb4_general_ci,
  `seo_description_en` text COLLATE utf8mb4_general_ci,
  `seo_keywords_tr` text COLLATE utf8mb4_general_ci,
  `seo_keywords_en` text COLLATE utf8mb4_general_ci,
  `seo_og_image` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seo_canonical` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kisa_aciklama` text COLLATE utf8mb4_general_ci,
  `detayli_aciklama` text COLLATE utf8mb4_general_ci,
  `oncesi_resim` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sonrasi_resim` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `galeri_json` text COLLATE utf8mb4_general_ci,
  `sss_json` text COLLATE utf8mb4_general_ci,
  `adimlar_json` text COLLATE utf8mb4_general_ci,
  `avantajlar_json` text COLLATE utf8mb4_general_ci,
  `teknikler_json` text COLLATE utf8mb4_general_ci,
  `sure` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hero_baslik` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hero_alt_baslik` text COLLATE utf8mb4_general_ci,
  `ozet_sure_etiket` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Tedavi Süresi',
  `randevu_buton_yazi` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Randevu Al',
  `paylas_buton_yazi` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Paylaş',
  `doktor_ad` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'Prof. Dr. İbrahim Duran',
  `doktor_unvan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'Ağız, Diş ve Çene Cerrahisi',
  `doktor_aciklama` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'Prof. Dr. İbrahim Duran, Protetik Diş Tedavisi ve İmplant Uzmanı',
  `doktor_buton_yazi` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Özgeçmişi İncele',
  `brosur_baslik` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Tedavi Broşürü',
  `brosur_aciklama` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'Detaylı tedavi bilgilerini PDF olarak indirin.',
  `brosur_buton_yazi` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'PDF İndir',
  `ilgili_tedaviler_baslik` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'İlginizi Çekebilecek Tedaviler',
  `randevu_cta_baslik` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `randevu_cta_aciklama` text COLLATE utf8mb4_general_ci,
  `telefon` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '0372 123 45 67',
  `video_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instagram_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sira` int DEFAULT '0',
  `aktif` tinyint DEFAULT '1',
  `silindi` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `seo_robots` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'index, follow'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tedaviler_seo_ayarlar`
--

CREATE TABLE `tedaviler_seo_ayarlar` (
  `id` int NOT NULL,
  `anahtar` varchar(100) NOT NULL,
  `deger` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `tedaviler_yedek_20260923`
--

CREATE TABLE `tedaviler_yedek_20260923` (
  `id` int NOT NULL DEFAULT '0',
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `baslik` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `breadcrumb` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Tedaviler',
  `kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `seo_title_tr` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seo_title_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seo_description_tr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `seo_description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `seo_keywords_tr` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `seo_keywords_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `seo_og_image` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `seo_canonical` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kisa_aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `detayli_aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `oncesi_resim` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sonrasi_resim` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `galeri_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sss_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `adimlar_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `avantajlar_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `teknikler_json` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `sure` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hero_baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hero_alt_baslik` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ozet_sure_etiket` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Tedavi Süresi',
  `randevu_buton_yazi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Randevu Al',
  `paylas_buton_yazi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Paylaş',
  `doktor_ad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Prof. Dr. İbrahim Duran',
  `doktor_unvan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Ağız, Diş ve Çene Cerrahisi',
  `doktor_aciklama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '15+ yıllık deneyim, 5000+ başarılı tedavi',
  `doktor_buton_yazi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Özgeçmişi İncele',
  `brosur_baslik` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Tedavi Broşürü',
  `brosur_aciklama` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'Detaylı tedavi bilgilerini PDF olarak indirin.',
  `brosur_buton_yazi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'PDF İndir',
  `ilgili_tedaviler_baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'İlginizi Çekebilecek Tedaviler',
  `randevu_cta_baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `randevu_cta_aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `telefon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '0372 123 45 67',
  `video_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `instagram_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sira` int DEFAULT '0',
  `aktif` tinyint DEFAULT '1',
  `silindi` tinyint DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `seo_robots` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'index, follow'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tablo dizinleri
--
-- NOT: Orijinal dump'ta bu kısım kesilmişti. Aşağıda her tablo için
--      standart PRIMARY KEY ve AUTO_INCREMENT tanımları eklendi.
--      (Kendi dump'ında AUTO_INCREMENT değerleri farklı olabilir,
--      istersen güncelleyebilirsin.)
--

ALTER TABLE `anasayfa_icerik`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `anasayfa_icerik`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `anasayfa_slider`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `anasayfa_slider`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `ayarlar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anahtar` (`anahtar`);

ALTER TABLE `ayarlar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `blog_aboneler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `blog_aboneler`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `blog_kategoriler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kategori_slug` (`kategori_slug`);

ALTER TABLE `blog_kategoriler`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `blog_seo_ayarlar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anahtar` (`anahtar`);

ALTER TABLE `blog_seo_ayarlar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `blog_sss`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `blog_sss`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `blog_yazilar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

ALTER TABLE `blog_yazilar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `blog_yazilar_silinmis_backup`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `commands`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `commands`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `dinamik_sayfalar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

ALTER TABLE `dinamik_sayfalar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `dinamik_sayfa_bloklari`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sayfa_id` (`sayfa_id`);

ALTER TABLE `dinamik_sayfa_bloklari`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `dosya_yollari`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anahtar` (`anahtar`);

ALTER TABLE `dosya_yollari`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `galeri`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `galeri`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `galeri_ayarlar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anahtar` (`anahtar`);

ALTER TABLE `galeri_ayarlar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `galeri_kategoriler`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `galeri_kategoriler`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `galeri_resimler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

ALTER TABLE `galeri_resimler`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `galeri_resimler_yedek_20260922`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `iletisim_mesajlari`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `iletisim_mesajlari`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `iletisim_seo_ayarlar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anahtar` (`anahtar`);

ALTER TABLE `iletisim_seo_ayarlar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `kullanicilar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kullanici_adi` (`kullanici_adi`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `kullanicilar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `kurumsal_ayarlari`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ayar_key` (`ayar_key`);

ALTER TABLE `kurumsal_ayarlari`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `page_slider`
  ADD PRIMARY KEY (`id`),
  ADD KEY `page_slug` (`page_slug`);

ALTER TABLE `page_slider`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `randevular`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `randevular`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `rol_yetkileri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `rol_modul_yetki` (`rol`,`modul`,`yetki`);

ALTER TABLE `rol_yetkileri`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `site_ayarlari`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ayar_key` (`ayar_key`);

ALTER TABLE `site_ayarlari`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `site_cevirileri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `metin_hash` (`metin_hash`);

ALTER TABLE `site_cevirileri`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `site_istatistikler`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tarih` (`tarih`),
  ADD KEY `ip` (`ip`);

ALTER TABLE `site_istatistikler`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `sss`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `sss`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `tedaviler`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

ALTER TABLE `tedaviler`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `tedaviler_seo_ayarlar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `anahtar` (`anahtar`);

ALTER TABLE `tedaviler_seo_ayarlar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `tedaviler_yedek_20260923`
  ADD PRIMARY KEY (`id`);

-- --------------------------------------------------------

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;