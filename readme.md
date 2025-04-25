## Install dependencies:
```bash
composer install
```

## Generate the local .env file:

```bash
composer dump-env dev
```

## Create the database:

```bash
php bin/console doctrine:database:create
```



## Add default data:

```bash
php bin/console app:add-default-data --entity=Car --file=cars-sample
```

## Usage
Start the Symfony server:

```bash
symfony server:start
```

Access the API at: http://127.0.0.1:8000/api/


## Example test data for creating a reservation:


```bash
{
"car": 1,
"userEmail": "toavina@gmail.com",
"startAt": "2025-04-24T12:58:36.781Z",
"endAt": "2025-04-25T12:58:36.781Z"
}
```