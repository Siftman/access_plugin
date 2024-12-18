# Shopino Plugin

## Overview

The Shopino Plugin is a WordPress plugin designed to enhance e-commerce functionality by providing custom API endpoints for managing products and orders. This plugin integrates seamlessly with WooCommerce, allowing developers to interact with product data and create orders through RESTful API calls.

## Features

- **Custom API Endpoints**: The plugin registers two main API endpoints:
  - `GET /wp-json/custome/v1/products`: Retrieves a list of all published products.
  - `POST /wp-json/custome/v1/create-order`: Creates a new order based on the provided billing and line item details.

- **Product Data Retrieval**: The plugin fetches comprehensive product details, including:
  - ID, name, slug, permalink, type, price, stock status, and images.
  - Categories and attributes associated with each product.

- **Order Creation**: The plugin allows for the creation of orders with the following features:
  - Validation of required fields (billing information and line items).
  - Support for setting shipping addresses and payment methods.
  - Automatic calculation of order totals.

## Installation

1. Download the Shopino Plugin files.
2. Upload the plugin folder to the `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage

### API Endpoints

#### Get All Products

- **Endpoint**: `GET /wp-json/custome/v1/products`
- **Response**: Returns a JSON object containing the total number of products and an array of product details.

#### Create Order

- **Endpoint**: `POST /wp-json/custome/v1/create-order`
- **Request Body**: A JSON object containing:
  - `billing`: An object with billing details.
  - `line_items`: An array of items to be included in the order, each containing `product_id` and `quantity`.
  - Optional fields: `shipping`, `payment_method`, and `status`.

- **Response**: Returns a JSON object indicating success and details of the created order, including `order_id`, `order_number`, and `total`.

### Example Request to Create an Order
#### json
```json
POST /wp-json/custome/v1/create-order   
{
"billing": {
"first_name": "John",
"last_name": "Doe",
"address_1": "123 Main St",
"city": "Anytown",
"state": "CA",
"postcode": "90210",
"country": "US",
"email": "john.doe@example.com",
"phone": "1234567890"
},
"line_items": [
{
"product_id": 123,
"quantity": 2
}
]
}
```

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher