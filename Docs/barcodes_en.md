# 📦 Documentation: Creating Barcodes for Inventory Items

This document describes the data structure of the barcode used for handling goods. The system supports various types of item movements between warehouses, customers, or during disposal.

## 🧾 Barcode Content

Each barcode contains the following data in the specified order:

| Order | Field Name            | Description |
|-------|-----------------------|------------|
| 1.    | **movement_type**      | Movement type: `1 = send`, `2 = receive`, `3 = move`, `4 = destroy` |
| 2.    | **item_type_id**       | Numeric ID of the item type (e.g., `101` for "USB cable") |
| 3.    | **from_warehouse_id**  | ID of the warehouse the item is leaving from *(or `0` if not relevant)* |
| 4.    | **to_warehouse_id**    | ID of the target warehouse *(or `0` if going to a customer, for example)* |
| 5.    | **amount_in_package**  | Number of units in one package (e.g., 25) |
| 6.    | **specific_item_id**   | Specific ID of the physical item *(or `0` if it's a new item)* |
| 7.    | **attributes** _(optional)_ | Custom attributes of the item, serialized at the end – e.g., color, size, batch |

> 🔁 **Note**: All fields 1–6 can be converted to a purely numeric format in case of scanner malfunction. In this case, the system must be able to decode the numbers back (e.g., mapping `movement_type = 1 → SEND`).

## 🛠️ Example Data in JSON Format

```json
{
  "movement_type": 3,
  "item_type_id": 101,
  "from_warehouse_id": 12,
  "to_warehouse_id": 0,
  "amount_in_package": 50,
  "specific_item_id": 987654,
  "attributes": {
    "color": "blue",
    "length": "1.5m"
  }
}
```
🔄 Possible Numeric Representation for Scanning
```
3|101|12|0|50|987654|blue;1.5m;
```

## 🏷️ Example Barcode

![Barcode image](./barcode.gif "Example barcode")

> 🔐 Attributes are always placed at the end and separated by semicolons so they do not interfere with the fixed parser.

---

## 📌 Summary

- **Attributes are optional and always last.**
- **All fields except attributes can be represented by numbers.**
- **`movement_type` determines the context of the operation** – it is key to processing the barcode correctly.
- **The system should validate the structure of the barcode based on the number of fields and data types.**
