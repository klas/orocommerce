# Oro\Bundle\InventoryBundle\Entity\InventoryLevel

## ACTIONS

### get

Retrieve a specific inventory level record.

{@inheritdoc}

### get_list

Retrieve a collection of inventory level records.

{@inheritdoc}

### update

Edit a specific inventory level record.

The updated record is returned in the response.

{@inheritdoc}

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "inventorylevels",
    "id": "1",
    "attributes": {
      "quantity": "51"
    },
    "relationships": {
      "product": {
        "data": {
          "type": "products",
          "id": "1"
        }
      },
      "productUnitPrecision": {
        "data": {
          "type": "productunitprecisions",
          "id": "1"
        }
      },
      "warehouse": {
        "data": {
          "type": "warehouses",
          "id": "1"
        }
      }
    }
  }
}
```
{@/request}

## FIELDS

### quantity

#### update

{@inheritdoc}

**Please note:**

*This field is **required** and must remain defined.*

## SUBRESOURCES

### product

#### get_subresource

Retrieve a record of product assigned to a specific inventory level record.

#### get_relationship

Retrieve the ID of the product record assigned to a specific inventory level record.

#### update_relationship

Replace the product assigned to a specific inventory level record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "products",
    "id": "1"
  }
}
```
{@/request}

### productUnitPrecision

#### get_subresource

Retrieve a record of the product unit precision assigned to a specific inventory level record.

#### get_relationship

Retrieve the ID of the product unit precision record assigned to a specific inventory level record.

#### update_relationship

Replace the product unit precision assigned to a specific inventory level record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "productunitprecisions",
    "id": "1"
  }
}
```
{@/request}

### warehouse

#### get_subresource

Retrieve a record of the warehouse assigned to a specific inventory level record.

#### get_relationship

Retrieve the ID of the warehouse record assigned to a specific inventory level record.

#### update_relationship

Replace the warehouse assigned to a specific inventory level record.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "warehouses",
    "id": "1"
  }
}
```
{@/request}

### organization

#### get_subresource

Retrieve the record of the organization a specific inventory level belongs to.

#### get_relationship

Retrieve the ID of the organization that a specific inventory level belongs to.

#### update_relationship

Replace the organization that a specific inventory level belongs to.

{@request:json_api}
Example:

```JSON
{
  "data": {
    "type": "organizations",
    "id": "1"
  }
}
```
{@/request}
