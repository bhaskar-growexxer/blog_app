const INDEX_NAME = 'products';

const indexMapping = {
  properties: {
    name: { type: 'text' },
    product_type: { type: 'text' },
    product_source: { type: 'text' },
    product_brand: { type: 'text' },
    description: { type: 'text' },
    client_name: { type: 'text' },
    brand_name: { type: 'text' },
    fsvp_status: { type: 'text' },
    vintage: { type: 'integer' },
    ttb_id: { type: 'text' },
    upc: { type: 'text' },
    scc: { type: 'text' },
    scc: { type: 'text' },
    bottle_dimensions: { type: 'float' },
    bottle_weight: { type: 'float' },
    case_dimensions: { type: 'float' },
    case_weight: { type: 'float' },
    pallet_dimensions: { type: 'float' },
    pallet_weight: { type: 'float' },
    layers_per_pallet: { type: 'integer' },
    cases_per_layer: { type: 'integer' },
    cases_per_pallet: { type: 'integer' },
    bottles_per_case: { type: 'integer' },
    nabca_code: { type: 'text' },
    price: { type: 'float' },
    uom: { type: 'text' },
    abv: { type: 'float' },
    volume: { type: 'float' },
    category: { type: 'keyword' },
    stock: { type: 'integer' },
    createdAt: { type: 'date' },
    updatedAt: { type: 'date' }
  }
};

module.exports = { INDEX_NAME, indexMapping };