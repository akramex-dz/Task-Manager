Symfony Task Managemen

This is a Symfony-based Task Management App that allows users to create, update, delete, and search for tasks.
🚀 Prerequisites

Before setting up the project, ensure you have the following installed:

    PHP 8.1 or higher

    Composer

    Symfony CLI

    MySQL or PostgreSQL

    Node.js & npm (optional, for frontend assets)

📥 Installation

    Clone the Repository:
    git clone https://github.com/your-repo/task-manager.git
    cd task-manager

    Install Dependencies:
    composer install

    Create the .env.local file and configure the database connection:
    cp .env .env.local

    Update the database URL in .env.local:
    DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/db_name"

    Setup the Database:
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate

    Load Fixtures (Optional, for test data):
    php bin/console doctrine:fixtures:load

🏃 Running the Project

    Start the Symfony Server:
    symfony server:start
