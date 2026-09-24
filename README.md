Dental Clinic Web Application
Bu proje, diş klinikleri için özel olarak geliştirilmiş; dinamik vaka yönetimi, blog, randevu ve arama motoru optimizasyonu (SEO) altyapısına sahip profesyonel bir web uygulamasıdır.

🚀 Kurulum Adımları (Installation)
Projeyi yerel sunucunuza (Localhost) veya canlı sunucunuza kurmak için aşağıdaki adımları sırasıyla takip edebilirsiniz:

Depoyu Klonlayın veya İndirin:
Proje dosyalarını sunucunuzun ana dizinine (htdocs veya www) aktarın.

Veritabanı Kurulumu:

MySQL sunucunuzda boş bir veritabanı oluşturun.

Proje ana dizininde yer alan klinik.sql dosyasını oluşturduğunuz bu veritabanına import edin (içe aktarın).

Veritabanı Bağlantı Ayarları:

admin/includes/config.php (veya ilgili yapılandırma) dosyasını bir metin düzenleyici ile açın.

Kendi MySQL sunucu bilgilerinizi (DB_HOST, DB_USER, DB_PASS, DB_NAME) girerek bağlantıyı yapılandırın.

API ve URL Yapılandırmaları:

api/ klasörü içerisindeki PHP dosyalarını ve diğer ana sayfa dosyalarını kontrol edin.

Kendi alan adınızı ([https://www.ornekalanadiniz.com](https://www.ornekalanadiniz.com)) ilgili alan adı sabitlerine (URL değişkenlerine) tanımlayın.

Güvenlik Notu (Önemli):

Google Search Console veya benzeri API entegrasyonları için kullanılan gerçek servis hesabı JSON anahtar dosyalarınızı (örnek: search-console-api-*.json) asla halka açık repolara commit etmeyin. Kendi sunucunuzda güvenli dizinlerde saklayın.

🛠️ Kullanılan Teknolojiler
Backend: PHP, MySQL

Frontend: HTML5, CSS3, JavaScript, Tailwind CSS

Araçlar & Entegrasyonlar: Google Search Console API
