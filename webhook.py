from flask import Flask, request, jsonify
import hmac
import hashlib
import logging
from functools import wraps
import os
from datetime import datetime

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    filename='webhook_listener.log'
)
logger = logging.getLogger('woocommerce_webhook')

app = Flask(__name__)
app.config['DEBUG'] = True 

WEBHOOK_SECRET = os.environ.get('WOOCOMMERCE_WEBHOOK_SECRET', None)
ALLOWED_TOPICS = [
    'order.created',
    'order.updated',
    'order.deleted',
    'product.created',
    'product.updated',
    'product.deleted',
    'customer.created',
    'customer.updated',
    'customer.deleted'
]

def verify_webhook_signature(f):
    @wraps(f)
    def decorated_function(*args, **kwargs):
        wc_signature = request.headers.get('X-WC-Webhook-Signature')
        if not wc_signature:
            logger.error('No webhook signature found in request headers')
            return jsonify({'error': 'No signature provided'}), 401

        payload = request.get_data()
        
        expected_signature = hmac.new(
            WEBHOOK_SECRET.encode('utf-8'),
            payload,
            hashlib.sha256
        ).hexdigest()

        if not hmac.compare_digest(wc_signature, expected_signature):
            logger.error('Invalid webhook signature')
            return jsonify({'error': 'Invalid signature'}), 401

        return f(*args, **kwargs)
    return decorated_function

@app.route('/staging/api/webhook/woocommerce', methods=['POST'])
# @verify_webhook_signature
def webhook_listener():
    logger.info(f'Incoming headers: {request.headers}')
    print("in webhook listener!")
    try:
        raw_data = request.get_data(as_text=True)
        logger.info(f'Raw request data: {raw_data}')

        if request.content_type == 'application/x-www-form-urlencoded':
            print("here in form urlencoded")
            webhook_data = request.form.to_dict()
        else:
            print("here in json")
            webhook_data = request.json
        
        webhook_id = webhook_data.get('webhook_id')
        if webhook_id:
            logger.info(f'Webhook initialized with ID: {webhook_id}')
            return jsonify({
                'status': 'success',
                'message': f'Webhook {webhook_id} initialized successfully'
            }), 200

        topic = request.headers.get('X-WC-Webhook-Topic')
        print(f'topic: {topic}')
        
        if not topic or topic not in ALLOWED_TOPICS:
            print(f'invalid or unsupported webhook topic: {topic}')
            logger.warning(f'Invalid or unsupported webhook topic: {topic}')
            return jsonify({'error': 'Invalid or unsupported webhook topic'}), 400

        logger.info(f'Received webhook: {topic}')
        logger.debug(f'Webhook data: {webhook_data}')

        if topic.startswith('order.'):
            handle_order_webhook(webhook_data, topic)
        elif topic.startswith('product.'):
            handle_product_webhook(webhook_data, topic)
        elif topic.startswith('customer.'):
            handle_customer_webhook(webhook_data, topic)

        return jsonify({
            'status': 'success',
            'message': f'Webhook {topic} processed successfully',
            'timestamp': datetime.utcnow().isoformat()
        }), 200

    except Exception as e:
        print(f'error in webhook listener: {e}')
        logger.error(f'Error processing webhook: {str(e)}', exc_info=True)
        return jsonify({
            'error': 'Internal server error',
            'message': str(e)
        }), 500

def handle_order_webhook(data, topic):
    order_id = data.get('id')
    logger.info(f'Processing {topic} for order {order_id}')

def handle_product_webhook(data, topic):
    product_id = data.get('id')
    logger.info(f'Processing {topic} for product {product_id}')

def handle_customer_webhook(data, topic):
    customer_id = data.get('id')
    logger.info(f'Processing {topic} for customer {customer_id}')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=9000, debug=True)
