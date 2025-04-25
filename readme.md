## Install dependencies:

composer install

## Generate the local .env file:

composer dump-env dev

## Create the database:

php bin/console doctrine:database:create



## Add default data:


php bin/console app:add-default-data --entity=Car --file=cars-sample

## Usage
Start the Symfony server:
symfony server:start

Access the API at: http://127.0.0.1:8000/api/


## Example test data for creating a reservation:


{
"car": 1,
"userEmail": "toavina@gmail.com",
"startAt": "2025-04-24T12:58:36.781Z",
"endAt": "2025-04-25T12:58:36.781Z"
}