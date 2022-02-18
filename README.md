# Mini Aspire API



## Installation
Laravel requires PHP 7.4+ to run.
- Download zip file or use git clone command to download
- Use composer install for install required dependencies
- Use php artisan migrate for create database
- Use php artisan serve command to start project
- Import postman collection, you can find collection in root directory with mini_aspire_collection.json

## API Information



- Registration : This api use for registration of users, First user will be an admin, those who able to approve loan
    Fields to be required
    > Email : Required
    > Password : Required
    > Confirm Password : Required
- Login : Use for authenticate users
    Fields to be required
    > Email : Required
    > Password : Required 
- Loan Apply : Use for apply loan, first loan will go for admin approval
Fields to be required
    > Term : Required
    > Loan Amount : Required
- Loan Approval : Use for approve loan, This api will use by admin only
Fields to be required
    > Loan Id : Required
    
- Loan List - Use for get list of all loans, This api will use for admin only
- Installment Payment : Use for pay loan installments, User have to login first to pay installment. User can't pay less than minimum installment amount (Loan amount / Terms)
 Fields to be required
    > Loan Id : Required
    > Amount : Required
