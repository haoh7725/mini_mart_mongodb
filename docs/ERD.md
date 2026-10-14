# ERD Mini Mart

```mermaid
erDiagram
    ROLES ||--o{ USERS : assigns
    USERS ||--o{ ADDRESSES : owns
    CATEGORIES ||--o{ PRODUCTS : groups
    BRANDS ||--o{ PRODUCTS : brands
    UNITS ||--o{ PRODUCTS : measures
    PRODUCTS ||--o{ PRODUCT_IMAGES : has
    PRODUCTS ||--o{ PRODUCT_VARIANTS : has
    WAREHOUSES ||--o{ INVENTORY_LOTS : stores
    PRODUCTS ||--o{ INVENTORY_LOTS : stocked
    SUPPLIERS ||--o{ PURCHASE_ORDERS : supplies
    PURCHASE_ORDERS ||--o{ PURCHASE_ORDER_ITEMS : contains
    WAREHOUSES ||--o{ STOCK_MOVEMENTS : records
    USERS ||--o| CARTS : owns
    CARTS ||--o{ CART_ITEMS : contains
    USERS ||--o{ ORDERS : places
    COUPONS ||--o{ ORDERS : discounts
    ORDERS ||--o{ ORDER_ITEMS : contains
    ORDERS ||--o{ PAYMENTS : paid_by
    USERS ||--o{ REVIEWS : writes
    PRODUCTS ||--o{ REVIEWS : receives
```

`product_variants.options` lưu thuộc tính linh hoạt dạng JSON, ví dụ
`{"Màu sắc":"Đỏ","Kích thước":"L"}`. Tồn kho được quản lý theo kho, biến thể,
lô sản xuất và hạn sử dụng; mọi thay đổi số lượng được ghi trong `stock_movements`.
