Project : Webshop

.ENV file code is pasted at the bottom of this page.

Action Points : 
# Create DB scheema using > 'php artisan migrate' command [SQL DB schema is also in ./SQL folder at root ]

1. Import Masterdata : Import customer data using custom Artisan commands

# Import customer data using locally stored CSV file
php artisan import:customer-data

# Import customer data directly from CSV URL
php artisan import:customer-data-from-URL

2. Import Masterdata : Import product data Artisan commands [Import Masterdata]

# Import product data using locally stored CSV file
php artisan import:product-data

# Import product data directly from CSV URL
php artisan import:product-data-from-URL

# APIs : Postman collection is available in root folder > Webshop.postman_collectionV2.0 / Webshop.postman_collectionV2.1
# Screenshots are in ./screenshots folder

======================================= .ENV CODE ======================================

APP_NAME=Webshop

APP_ENV=local
APP_KEY=base64:6K+S/s8B3Nc+CoewJPFz4B51922+tNSXWvh1kyQj3cY=
APP_DEBUG=true
APP_URL=http://localhost

USERNAME_CSV=loop
PASSWORD_CSV=backend_dev
CUSTOMER_CSV_URL=https://backend-developer.view.agentur-loop.com/customers.csv
PRODUCT_CSV_URL=https://backend-developer.view.agentur-loop.com/products.csv
PAYMENT_URL=https://superpay.view.agentur-loop.com/pay

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_webshop
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

