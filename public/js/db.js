require("dotenv").config();
const mysql = require("mysql2/promise");
let connection;
async function getDbConnection() {
  if (!connection) {
    const config = {
      host: process.env.DB_HOST === 'localhost' ? '127.0.0.1' : process.env.DB_HOST,
      user: process.env.DB_USER,
      password: process.env.DB_PASSWORD,
      database: process.env.DB_NAME,
      port: parseInt(process.env.DB_PORT),
      // Add connection timeout
      connectTimeout: 10000,
    };
    
    console.log('Database connection config:', {
      host: config.host,
      user: config.user,
      database: config.database,
      port: config.port
    });
    
    connection = await mysql.createConnection(config);
  }
  return connection;
}
module.exports = getDbConnection;
