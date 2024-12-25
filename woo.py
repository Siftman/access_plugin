from flask import Flask, request, jsonify
import hmac
import hashlib
import logging
from functools import wraps
from datetime import datetime
import os

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    filename='webhook_listener.log'
)
logger = logging.getLogger('woocommerce_webhook')

app = Flask(__name__)
app.config['DEBUG'] = True 

WEBHOOK_SECRET = os.environ.get('WOOCOMMERCE_WEBHOOK_SECRET', None)

@app.route('/webhook/api/create-webhook', methods=['POST'])
def webhook_listener():
    try:
        print("request raw_data : ", request.get_data(as_text=True)) 

        if request.content_type == 'application/x-www-form-urlencoded':
            webhook_data = request.form.to_dict()
        else:
            webhook_data = request.json
        
        webhook_id = webhook_data.get('webhook_id')
        print(webhook_id)
        if webhook_id:
            return jsonify({
                'status': 'success',
                'message': f'Webhook {webhook_id} initialized successfully'
            }), 200

        topic = request.headers.get('X-WC-Webhook-Topic')
        print(topic)

        return jsonify({
            'status': 'success',
            'message': f'Webhook {topic} processed successfully',
            'timestamp': datetime.utcnow().isoformat()
        }), 200

    except Exception as e:
        return jsonify({
            'error': 'Internal server error',
            'message': str(e)
        }), 500


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=9999, debug=True)
