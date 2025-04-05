function loadOrders(status) {
  fetch(`get_orders.php?status=${status}`)
    .then((response) => response.json())
    .then((orders) => {
      const ordersList = document.getElementById("orders-list");
      ordersList.innerHTML = "";

      orders.forEach((order) => {
        const orderCard = createOrderCard(order);
        ordersList.appendChild(orderCard);
      });
    })
    .catch((error) => console.error("Error loading orders:", error));
}

function createOrderCard(order) {
  const card = document.createElement("div");
  card.className = "order-card";
  card.innerHTML = `
        <div class="order-details">
            <h3>Order #${order.order_id}</h3>
            <p>Product: ${order.product_name}</p>
            <p>Quantity: ${order.quantity}</p>
            <p>Total: ₹${order.total_price}</p>
            <p>Delivery Address: ${order.delivery_address}</p>
            <p>Instructions: ${order.special_instructions || "None"}</p>
            <p>Order Date: ${new Date(order.order_date).toLocaleString()}</p>
        </div>
        ${
          order.status === "pending"
            ? `
            <div class="order-actions">
                <button onclick="handleOrder(${order.order_id}, 'accepted')" class="accept-btn">Accept</button>
                <button onclick="handleOrder(${order.order_id}, 'rejected')" class="reject-btn">Reject</button>
            </div>
        `
            : `
            <div class="order-status ${order.status}">
                Status: ${
                  order.status.charAt(0).toUpperCase() + order.status.slice(1)
                }
            </div>
        `
        }
    `;
  return card;
}

function handleOrder(orderId, status) {
  if (!confirm(`Are you sure you want to ${status} this order?`)) return;

  const formData = new FormData();
  formData.append("order_id", orderId);
  formData.append("status", status);

  fetch("handle_order.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert(data.message);
        loadOrders("pending");
      } else {
        alert(data.error || "Error updating order");
      }
    })
    .catch((error) => console.error("Error:", error));
}
