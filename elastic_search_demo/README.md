Elasticsearch CRUD Demo
A simple Node.js application demonstrating CRUD (Create, Read, Update, Delete) operations with Elasticsearch.
📋 Module: Product Management
This demo implements a complete product management system with Elasticsearch as the backend database.
🚀 Prerequisites

Node.js (v10 or higher)
Elasticsearch 7.17.0

📁 Project Structure
elasticsearch-demo/
├── config/
│   └── elasticsearch.config.js    # ES connection configuration
├── models/
│   └── product.model.js            # Product index mapping
├── services/
│   └── product.service.js          # CRUD operations logic
├── controllers/
│   └── product.controller.js       # Request handlers
├── routes/
│   └── product.routes.js           # API routes
├── app.js                          # Express app setup
├── server.js                       # Server entry point
├── package.json                    # Dependencies
└── README.md                       # Documentation

🛠️ Setup Instructions
1. Install Elasticsearch
bash# Download Elasticsearch 7.17.0
cd ~
wget https://artifacts.elastic.co/downloads/elasticsearch/elasticsearch-7.17.0-linux-x86_64.tar.gz

# Extract
tar -xzf elasticsearch-7.17.0-linux-x86_64.tar.gz

# Start Elasticsearch (in a separate terminal)
cd elasticsearch-7.17.0
./bin/elasticsearch
Wait 30-60 seconds for Elasticsearch to start.
2. Verify Elasticsearch is Running
bashcurl http://localhost:9200
3. Install Project Dependencies
bashnpm install
4. Start the Application
bashnpm start