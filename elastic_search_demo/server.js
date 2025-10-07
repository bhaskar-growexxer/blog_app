const app = require('./app');
const { checkConnection } = require('./config/elasticsearch.config');
const productService = require('./services/product.service');

const PORT = process.env.PORT || 3000;

const startServer = async () => {
  try {
    // Check Elasticsearch connection
    await checkConnection();
    
    // Initialize products index
    await productService.initializeIndex();
    
    // Start server
    app.listen(PORT, () => {
      console.log(` Server is running on http://localhost:${PORT}`);
      console.log(` API Documentation:`);
      console.log(`   POST   http://localhost:${PORT}/api/products`);
      console.log(`   GET    http://localhost:${PORT}/api/products`);
      console.log(`   GET    http://localhost:${PORT}/api/products/:id`);
      console.log(`   PUT    http://localhost:${PORT}/api/products/:id`);
      console.log(`   DELETE http://localhost:${PORT}/api/products/:id`);
      console.log(`   GET    http://localhost:${PORT}/api/products/search?q=query`);
    });
  } catch (error) {
    console.error('❌ Failed to start server:', error.message);
    process.exit(1);
  }
};

startServer();