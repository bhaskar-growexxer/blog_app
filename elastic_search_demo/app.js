const express = require('express');
const bodyParser = require('body-parser');
const productRoutes = require('./routes/product.routes');

const app = express();

// Middleware
app.use(bodyParser.json());

// Routes
app.use('/api/products', productRoutes);

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'OK', message: 'Server is running' });
});

module.exports = app;