const { client } = require('../config/elasticsearch.config');
const { INDEX_NAME, indexMapping } = require('../models/product.model');

class ProductService {
  // Initialize index with mapping
  async initializeIndex() {
    try {
      const exists = await client.indices.exists({ index: INDEX_NAME });
      
      if (!exists) {
        await client.indices.create({
          index: INDEX_NAME,
          body: { mappings: indexMapping }
        });
        console.log(`✅ Index "${INDEX_NAME}" created successfully`);
      } else {
        console.log(`ℹ️  Index "${INDEX_NAME}" already exists`);
      }
    } catch (error) {
      console.error('Error initializing index:', error.message);
      throw error;
    }
  }

  // CREATE - Add new product
  async createProduct(productData) {
    try {
      const body = {
        ...productData,
        createdAt: new Date().toISOString(),
        updatedAt: new Date().toISOString()
      };

      const result = await client.index({
        index: INDEX_NAME,
        body,
        refresh: 'true'
      });

      return { id: result._id, ...body };
    } catch (error) {
      throw new Error(`Error creating product: ${error.message}`);
    }
  }

  // READ - Get all products
  async getAllProducts() {
    try {
      const result = await client.search({
        index: INDEX_NAME,
        body: {
          query: { match_all: {} },
          size: 100
        }
      });

      return result.hits.hits.map(hit => ({
        id: hit._id,
        ...hit._source
      }));
    } catch (error) {
      throw new Error(`Error fetching products: ${error.message}`);
    }
  }

  // READ - Get product by ID
  async getProductById(id) {
    try {
      const result = await client.get({
        index: INDEX_NAME,
        id
      });

      return { id: result._id, ...result._source };
    } catch (error) {
      if (error.meta && error.meta.statusCode === 404) {
        throw new Error('Product not found');
      }
      throw new Error(`Error fetching product: ${error.message}`);
    }
  }

  // UPDATE - Update product
  async updateProduct(id, updateData) {
    try {
      const body = {
        ...updateData,
        updatedAt: new Date().toISOString()
      };

      await client.update({
        index: INDEX_NAME,
        id,
        body: { doc: body },
        refresh: 'true'
      });

      return await this.getProductById(id);
    } catch (error) {
      if (error.meta && error.meta.statusCode === 404) {
        throw new Error('Product not found');
      }
      throw new Error(`Error updating product: ${error.message}`);
    }
  }

  // DELETE - Delete product
  async deleteProduct(id) {
    try {
      await client.delete({
        index: INDEX_NAME,
        id,
        refresh: 'true'
      });

      return { message: 'Product deleted successfully', id };
    } catch (error) {
      if (error.meta && error.meta.statusCode === 404) {
        throw new Error('Product not found');
      }
      throw new Error(`Error deleting product: ${error.message}`);
    }
  }

  // SEARCH - Search products by query
  async searchProducts(query) {
    try {
      const result = await client.search({
        index: INDEX_NAME,
        body: {
          query: {
            multi_match: {
              query,
              fields: ['name', 'description', 'category']
            }
          }
        }
      });

      return result.hits.hits.map(hit => ({
        id: hit._id,
        score: hit._score,
        ...hit._source
      }));
    } catch (error) {
      throw new Error(`Error searching products: ${error.message}`);
    }
  }
}

module.exports = new ProductService();