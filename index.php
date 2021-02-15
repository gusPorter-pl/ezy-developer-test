<!DOCTYPE html>
<html>
  <head>
    <title>Shopping Cart</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="./styles.css" type="text/css"/>
  </head>
  <body>
    <?php
      // ######## please do not alter the following code ########
      $products = [
        [ "name" => "Sledgehammer", "price" => 125.75 ],
        [ "name" => "Axe", "price" => 190.50 ],
        [ "name" => "Bandsaw", "price" => 562.131 ],
        [ "name" => "Chisel", "price" => 12.9 ],
        [ "name" => "Hacksaw", "price" => 18.45 ],
      ];
      // ########################################################

      class ShoppingCart {
        private $items;
        private $database;

        public function __construct ($products, $database) {
          $this->items = $products;
          $this->database = $database;
        }

        public function get_items () {
          return $this->items;
        }

        public function get_total_price () {
          $table_data = $this->database->get_table();
          $total = 0.0;
          while ($item = $table_data->fetch_assoc()) {
            $total += $item['quantity'] * $item['price'];
          }
          return $total;
        }

        // TODO: remove
        public function increase_quantity ($item_name) {
          foreach ($this->items as $item) {
            if ($item['name'] == $item_name) {
              $item['quantity']++;
            }
          }
        }
      }

      class ShoppingCartDatabase {
        private $server;
        private $database;
        private $table;
        private $user;
        private $password;
        private $items;
        private $connection;

        public function __construct ($items) {
          $this->server = "localhost";
          $this->database = "cart_database";
          $this->table = "cart_contents";
          $this->user = "root";
          $this->password = "";
          $this->items = $items;

          $this->set_up_database();
        }
        
        private function set_up_database () {
          $this->connection = mysqli_connect($this->server, $this->user, $this->password);
  
          if (!$this->connection) {
            echo "Connection Failed! " . mysqli_connect_error();
          }
  
          $query = "CREATE DATABASE IF NOT EXISTS $this->database";
          if (mysqli_query($this->connection, $query)) {
            $this->connection = mysqli_connect($this->server, $this->user, $this->password, $this->database);
  
            $query = "
              CREATE TABLE IF NOT EXISTS $this->table(
                id INT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                item_name VARCHAR(32) NOT NULL,
                price FLOAT NOT NULL,
                quantity INT(5) NOT NULL
              )
            ";
            if (!mysqli_query($this->connection, $query)) {
              echo "Error! " . mysqli_error($this->connection);
            } else if ($this->row_number() == 0) {
              $this->populate_table();
            }
          }
        }

        private function row_number () {
          $table_data = $this->get_table();
          // while ($row = $table_data->fetch_assoc()) {
          //   echo "id: " . $row["id"] . ", Product: " . $row["item_name"] . ", Price: " . $row["price"] . ", Quantity: " . $row["quantity"] . "<br>";
          // }
          return $table_data->num_rows;
        }
        
        private function populate_table () {
          foreach ($this->items as $item) {
            $this->connection = mysqli_connect($this->server, $this->user, $this->password, $this->database);
            $query = "
              INSERT INTO " . $this->table . " (
                item_name, price, quantity
              ) VALUES ('" .
                $item['name'] . "', " . $item['price'] . ", 0
              )
            ";
            if (!mysqli_query($this->connection, $query)) {
              echo "Error! " . mysqli_error($this->connection);
            }
          }
        }
        
        public function get_table () {
          $query = "SELECT * FROM $this->table";
          $table_data = $this->connection->query($query);
          return $table_data;
        }

        public function add_item ($item_name) {
          $table_data = $this->get_table();
          while ($row = $table_data->fetch_assoc()) {
            if ($row["item_name"] == $item_name) {
              $this->connection = mysqli_connect($this->server, $this->user, $this->password, $this->database);
              $query = "
                UPDATE " . $this->table .
                " SET " .
                  "item_name = '" . $row['item_name'] . "', " .
                  "price = " . $row['price'] . ", " .
                  "quantity = " . strval($row['quantity'] + 1) .
                " WHERE item_name = '" . $row['item_name'] . "'"
              ;
              // echo $query;
              if (!mysqli_query($this->connection, $query)) {
                echo "Error! " . mysqli_error($this->connection);
              }
              break;
            }
          }
        }

        public function remove_item ($item_name) {
          $table_data = $this->get_table();
          while ($row = $table_data->fetch_assoc()) {
            if ($row["item_name"] == $item_name) {
              $this->connection = mysqli_connect($this->server, $this->user, $this->password, $this->database);
              $query = "
                UPDATE " . $this->table .
                " SET " .
                  "item_name = '" . $row['item_name'] . "', " .
                  "price = " . $row['price'] . ", " .
                  "quantity = 0" .
                " WHERE item_name = '" . $row['item_name'] . "'"
              ;
              // echo $query;
              if (!mysqli_query($this->connection, $query)) {
                echo "Error! " . mysqli_error($this->connection);
              }
              break;
            }
          }
        }
      }

      session_start();
      $db = new ShoppingCartDatabase($products);
      $cart = new ShoppingCart($products, $db);

      if (isset($_POST['add'])) {
        $item_name =  $_POST['add_item_input'];
        $db->add_item($item_name);
        header("Location: ./index.php");
      }

      if (isset($_POST['remove'])) {
        $item_name =  $_POST['remove_item_input'];
        $db->remove_item($item_name);
        header("Location: ./index.php");
      }
    ?>

    <h1>Product List</h1>
    <table>
      <tr>
        <td>Item</td>
        <td>Price</td>
        <td></td>
      </tr>
      <?php foreach ($products as $item) {
        $item_name = $item['name'];
      ?>
        <tr>
          <td><?=$item_name ?></td>
          <td><?=number_format($item['price'], 2, ".", ",") ?></td>
          <td>
            <form method="post">
              <button type="submit" name="add">Add to cart</button>
              <input type="hidden" name="add_item_input" value="<?php echo $item_name ?>">
            </form>
          </td>
        </tr>
      <?php } ?>
    </table>

    <?php if ($cart->get_total_price() == 0) { ?>
    <h3>Empty Cart! Add items!</h3>
    <?php } else { ?>

    <h1>Shopping Cart</h1>
    <table>
      <tr>
        <td>Item</td>
        <td>Price</td>
        <td>Quantity</td>
        <td>Total Price</td>
      </tr>
      <?php $table_data = $db->get_table();
        while ($item = $table_data->fetch_assoc()) {
          $item_name = $item['item_name'];
          if ($item['quantity'] > 0) {?>
            <tr>
              <td><?=$item['item_name'] ?></td>
              <td><?=number_format($item['price'], 2, ".", ",") ?></td>
              <td><?=$item['quantity'] ?></td>
              <td>$<?=number_format($item['price'] * $item['quantity'], 2, ".", ",")?></td>
              <td>
                <form method="post">
                  <button type="submit" name="remove">Remove from cart</button>
                  <input type="hidden" name="remove_item_input" value="<?php echo $item_name ?>">
                </form>
              </td>
            </tr>
          <?php
          }
        }
      ?>
    </table>

    <h2>Total: </h3>
    <p>$<?=number_format($cart->get_total_price(), 2, ".", ",")?></p>
    <?php } ?>
  </body>
</html>
