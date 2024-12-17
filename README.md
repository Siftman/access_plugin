# Shopino Access API Plugin

A WordPress plugin that provides secure API endpoints for WooCommerce data retrieval and order submission.

## Features

- Secure API key authentication
- Retrieve all WordPress posts
- Retrieve all WooCommerce products with detailed information
- Create WooCommerce orders programmatically
- Easy-to-use admin interface for API key management

## Requirements

- WordPress 5.6 or higher
- PHP 7.4 or higher
- WooCommerce 5.0 or higher

## Installation

1. Download the plugin files and upload them to your `/wp-content/plugins/shopino-access-api` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'Shopino API' menu in your WordPress admin panel
4. Copy your API key for use in external applications

## API Endpoints

### Authentication

All API requests must include the following header:
```
X-Shopino-API-Key: your_api_key
```

### Available Endpoints

#### Get All Products
```
GET /wp-json/shopino/v1/products
```

Response includes:
- Product ID
- Name
- Description
- Price information
- Stock status
- Categories
- Images
- And more...

#### Get All Posts
```
GET /wp-json/shopino/v1/posts
```

Response includes:
- Post ID
- Title
- Content
- Excerpt
- Featured image
- Categories
- Tags
- Publication date

#### Create Order
```
POST /wp-json/shopino/v1/orders
```

Required parameters:
```json
{
    "customer_email": "customer@example.com",
    "items": [
        {
            "product_id": 123,
            "quantity": 1
        }
    ]
}
```

Optional parameters:
```json
{
    "customer_data": {
        "first_name": "John",
        "last_name": "Doe",
        "address_1": "123 Main St",
        "city": "Example City",
        "state": "EX",
        "postcode": "12345",
        "country": "US",
        "phone": "123-456-7890"
    }
}
```

## Security

- All endpoints are protected with API key authentication
- API keys can be regenerated at any time through the admin interface
- HTTPS is strongly recommended for all API communications

## Support

For support or feature requests, please open an issue in the GitHub repository.
