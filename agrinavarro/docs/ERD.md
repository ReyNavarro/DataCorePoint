# Agriculture Information System ERD

This diagram is based on the normalized Supabase schema screenshot supplied in the normalization DOCX. The embedded screenshot is the source ERD captured from Supabase; the Mermaid diagram below is an editable text version for GitHub and future updates.

![Supabase schema diagram](erd-supabase-source.png)

```mermaid
erDiagram
    roles ||--o{ users : assigns
    users ||--o{ activity_logs : records
    farmers ||--o{ farms : owns
    soil_types ||--o{ farms : classifies
    irrigation_types ||--o{ farms : irrigates
    crop_categories ||--o{ crops : categorizes
    seasons ||--o{ crops : recommends
    farms ||--o{ plantings : hosts
    crops ||--o{ plantings : selects
    planting_statuses ||--o{ plantings : tracks
    plantings ||--o{ harvests : produces
    quality_grades ||--o{ harvests : grades
    harvest_remarks ||--o{ harvests : describes

    roles {
        int role_id PK
        varchar role_name
    }
    users {
        int user_id PK
        varchar username UK
        varchar password_hash
        int role_id FK
    }
    activity_logs {
        int log_id PK
        int user_id FK
        text log_action
        timestamp created_at
    }
    farmers {
        int farmer_id PK
        varchar first_name
        varchar last_name
        varchar contact_number
        varchar address
        date date_registered
    }
    farms {
        int farm_id PK
        int farmer_id FK
        varchar farm_name
        varchar location
        numeric area_hectares
        int soil_id FK
        int irrigation_id FK
    }
    soil_types {
        int soil_id PK
        varchar soil_name
    }
    irrigation_types {
        int irrigation_id PK
        varchar irrigation_name
    }
    crops {
        int crop_id PK
        varchar crop_name
        varchar variety
        int category_id FK
        int growing_period_days
        int season_id FK
    }
    crop_categories {
        int category_id PK
        varchar category_name
    }
    seasons {
        int season_id PK
        varchar season_name
    }
    plantings {
        int planting_id PK
        int farm_id FK
        int crop_id FK
        date planting_date
        numeric area_planted
        numeric seed_quantity
        date expected_harvest_date
        int status_id FK
    }
    planting_statuses {
        int status_id PK
        varchar status_name
    }
    harvests {
        int harvest_id PK
        int planting_id FK
        date harvest_date
        numeric quantity_kg
        numeric selling_price_per_kg
        int grade_id FK
        int remark_id FK
    }
    quality_grades {
        int grade_id PK
        varchar grade_name
    }
    harvest_remarks {
        int remark_id PK
        varchar remark_description
    }
```

`system_settings` is a key/value configuration table and is not connected to the operational records.

## Lookup behavior in the application

Forms display the descriptive lookup labels and submit their numeric IDs. For example, a harvest remark selection submits `remark_id`; the readable label is obtained by joining `harvest_remarks`. The current database also retains old descriptive columns such as `harvests.remarks` as `NOT NULL`. New application writes leave those legacy text fields empty and use the IDs. Older records may still contain text in those columns.

## Schema caveat

The lookup-normalization migration has been applied in Supabase. A live catalog audit found the duplicate label columns removed and required lookup IDs populated. The diagram remains a design aid; confirm it against the latest generated data dictionary before using it as the final schema contract.
