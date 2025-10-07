const { Client } = require('@elastic/elasticsearch');

const client = new Client({
  node: 'http://localhost:9200'
});

// Test connection
const checkConnection = async () => {
  try {
    await client.ping();
    console.log('Elasticsearch connected successfully');
  } catch (error) {
    console.error('Elasticsearch connection failed:', error.message);
  }
};

module.exports = { client, checkConnection };