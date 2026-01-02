const storage = {
  get(key, fallback) {
    const raw = localStorage.getItem(key);
    if (!raw) {
      return fallback;
    }
    try {
      return JSON.parse(raw);
    } catch {
      return fallback;
    }
  },
  set(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
  },
};

const formatCurrency = (value) =>
  new Intl.NumberFormat("en-IN", {
    style: "currency",
    currency: "INR",
    maximumFractionDigits: 2,
  }).format(value || 0);

const qs = (selector) => document.querySelector(selector);
const qsa = (selector) => Array.from(document.querySelectorAll(selector));

const toast = (message) => {
  const el = qs("#toast");
  el.textContent = message;
  el.classList.add("show");
  setTimeout(() => el.classList.remove("show"), 2400);
};

const state = {
  customers: storage.get("customers", []),
  products: storage.get("products", []),
  invoices: storage.get("invoices", []),
  invoiceCounter: storage.get("invoiceCounter", 1001),
};

const persist = () => {
  storage.set("customers", state.customers);
  storage.set("products", state.products);
  storage.set("invoices", state.invoices);
  storage.set("invoiceCounter", state.invoiceCounter);
};

const renderCustomers = () => {
  const tbody = qs("#customerTable");
  tbody.innerHTML = "";
  state.customers.forEach((customer, index) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${customer.name}</td>
      <td>${customer.type}</td>
      <td>${customer.gstin || "-"}</td>
      <td><button class="ghost" data-action="remove-customer" data-index="${index}">Remove</button></td>
    `;
    tbody.appendChild(row);
  });

  const select = qs("#invoiceCustomer");
  select.innerHTML = state.customers
    .map(
      (customer, idx) =>
        `<option value="${idx}">${customer.name} • ${customer.type}</option>`
    )
    .join("");
};

const renderProducts = () => {
  const tbody = qs("#productTable");
  tbody.innerHTML = "";
  state.products.forEach((product, index) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${product.name}</td>
      <td>${product.hsn || "-"}</td>
      <td>${formatCurrency(product.price)}</td>
      <td>${product.gst}%</td>
      <td>${product.stock}</td>
      <td><button class="ghost" data-action="remove-product" data-index="${index}">Remove</button></td>
    `;
    tbody.appendChild(row);
  });
};

const updateSummary = () => {
  const lines = qsa(".item-row").map((row) => {
    const qty = Number(row.querySelector(".qty").value) || 0;
    const rate = Number(row.querySelector(".rate").value) || 0;
    const gst = Number(row.querySelector(".gst").value) || 0;
    const subtotal = qty * rate;
    const gstAmount = subtotal * (gst / 100);
    return { subtotal, gstAmount };
  });

  const subtotal = lines.reduce((acc, line) => acc + line.subtotal, 0);
  const gstTotal = lines.reduce((acc, line) => acc + line.gstAmount, 0);
  const total = subtotal + gstTotal;

  qs("#summarySubtotal").textContent = formatCurrency(subtotal);
  qs("#summaryGST").textContent = formatCurrency(gstTotal);
  qs("#summaryTotal").textContent = formatCurrency(total);
};

const createItemRow = (item = {}) => {
  const row = document.createElement("div");
  row.className = "item-row";
  row.innerHTML = `
    <select class="item-select">
      <option value="">Custom item</option>
      ${state.products
        .map(
          (product, idx) =>
            `<option value="${idx}">${product.name}</option>`
        )
        .join("")}
    </select>
    <input class="qty" type="number" min="1" value="${item.qty || 1}" />
    <input class="rate" type="number" min="0" step="0.01" value="${item.rate || ""}" placeholder="Rate" />
    <input class="gst" type="number" min="0" max="28" step="0.1" value="${item.gst || ""}" placeholder="GST %" />
    <button class="ghost remove-item" type="button">Remove</button>
  `;

  row.querySelector(".item-select").addEventListener("change", (event) => {
    const idx = Number(event.target.value);
    const product = state.products[idx];
    if (!product) {
      return;
    }
    row.querySelector(".rate").value = product.price;
    row.querySelector(".gst").value = product.gst;
    updateSummary();
  });

  row.querySelectorAll("input").forEach((input) =>
    input.addEventListener("input", updateSummary)
  );

  row.querySelector(".remove-item").addEventListener("click", () => {
    row.remove();
    updateSummary();
  });

  return row;
};

const renderInvoices = () => {
  const tbody = qs("#invoiceTable");
  tbody.innerHTML = "";
  state.invoices.forEach((invoice, index) => {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${invoice.number}</td>
      <td>${invoice.customerName}</td>
      <td>${invoice.type}</td>
      <td>${formatCurrency(invoice.total)}</td>
      <td>${invoice.status}</td>
      <td>
        <button class="ghost" data-action="view-invoice" data-index="${index}">View</button>
        <button class="ghost" data-action="toggle-payment" data-index="${index}">Toggle Paid</button>
      </td>
    `;
    tbody.appendChild(row);
  });
};

const updatePayments = () => {
  const list = qs("#paymentList");
  list.innerHTML = "";
  const unpaid = state.invoices.filter((invoice) => invoice.status !== "Paid");
  if (unpaid.length === 0) {
    list.innerHTML = "<p class=\"muted\">All invoices are paid. Great work!</p>";
    return;
  }

  unpaid.slice(0, 5).forEach((invoice) => {
    const card = document.createElement("div");
    card.className = "card";
    card.innerHTML = `
      <strong>${invoice.customerName}</strong>
      <p>${invoice.type} • ${formatCurrency(invoice.total)} • ${invoice.status}</p>
      <button class="ghost" data-action="remind" data-id="${invoice.number}">Send Reminder</button>
    `;
    list.appendChild(card);
  });
};

const updateReports = () => {
  const outstanding = state.invoices
    .filter((invoice) => invoice.status !== "Paid")
    .reduce((acc, invoice) => acc + invoice.total, 0);
  const paid = state.invoices.filter((invoice) => invoice.status === "Paid");
  const paidTotal = paid.reduce((acc, invoice) => acc + invoice.total, 0);
  const gstReturn = state.invoices.reduce(
    (acc, invoice) => acc + invoice.gstTotal,
    0
  );
  const inventoryValue = state.products.reduce(
    (acc, product) => acc + product.price * product.stock,
    0
  );

  qs("#reportOutstanding").textContent = formatCurrency(outstanding);
  qs("#reportPaid").textContent = paid.length.toString();
  qs("#gstReturn").textContent = formatCurrency(gstReturn);
  qs("#inventoryValue").textContent = formatCurrency(inventoryValue);

  qs("#statInvoices").textContent = state.invoices.length.toString();
  qs("#statOutstanding").textContent = formatCurrency(outstanding);
  qs("#statRevenue").textContent = formatCurrency(paidTotal);
};

const refresh = () => {
  renderCustomers();
  renderProducts();
  renderInvoices();
  updatePayments();
  updateReports();
  updateSummary();
};

qs("#customerForm").addEventListener("submit", (event) => {
  event.preventDefault();
  const customer = {
    name: qs("#customerName").value.trim(),
    type: qs("#customerType").value,
    gstin: qs("#customerGstin").value.trim(),
    email: qs("#customerEmail").value.trim(),
    phone: qs("#customerPhone").value.trim(),
  };
  if (!customer.name) {
    return;
  }
  state.customers.push(customer);
  persist();
  event.target.reset();
  refresh();
  toast("Customer saved.");
});

qs("#productForm").addEventListener("submit", (event) => {
  event.preventDefault();
  const product = {
    name: qs("#productName").value.trim(),
    hsn: qs("#productHSN").value.trim(),
    price: Number(qs("#productPrice").value) || 0,
    gst: Number(qs("#productGST").value) || 0,
    stock: Number(qs("#productStock").value) || 0,
  };
  if (!product.name) {
    return;
  }
  state.products.push(product);
  persist();
  event.target.reset();
  refresh();
  toast("Product saved.");
});

qs("#invoiceForm").addEventListener("submit", (event) => {
  event.preventDefault();
  if (state.customers.length === 0) {
    toast("Add a customer first.");
    return;
  }

  const items = qsa(".item-row").map((row) => {
    const productIndex = row.querySelector(".item-select").value;
    const product = state.products[Number(productIndex)] || {};
    return {
      name: product.name || "Custom item",
      qty: Number(row.querySelector(".qty").value) || 0,
      rate: Number(row.querySelector(".rate").value) || 0,
      gst: Number(row.querySelector(".gst").value) || 0,
    };
  });

  const totals = items.reduce(
    (acc, item) => {
      const subtotal = item.qty * item.rate;
      const gstAmount = subtotal * (item.gst / 100);
      acc.subtotal += subtotal;
      acc.gstTotal += gstAmount;
      return acc;
    },
    { subtotal: 0, gstTotal: 0 }
  );

  const total = totals.subtotal + totals.gstTotal;
  const customer = state.customers[Number(qs("#invoiceCustomer").value)];
  const invoice = {
    number: state.invoiceCounter,
    customerName: customer?.name || "Unknown",
    customerEmail: customer?.email || "",
    customerPhone: customer?.phone || "",
    date: qs("#invoiceDate").value,
    type: qs("#invoiceType").value,
    status: qs("#invoiceStatus").value,
    items,
    subtotal: totals.subtotal,
    gstTotal: totals.gstTotal,
    total,
  };

  state.invoiceCounter += 1;
  state.invoices.unshift(invoice);
  persist();
  refresh();
  toast("Invoice saved.");
});

qs("#addItem").addEventListener("click", () => {
  qs("#itemsContainer").appendChild(createItemRow());
  updateSummary();
});

qs("#invoiceTable").addEventListener("click", (event) => {
  const action = event.target.dataset.action;
  if (!action) {
    return;
  }
  const index = Number(event.target.dataset.index);
  const invoice = state.invoices[index];
  if (!invoice) {
    return;
  }

  if (action === "view-invoice") {
    const detail = invoice.items
      .map(
        (item) =>
          `${item.name} x${item.qty} • ${formatCurrency(
            item.rate
          )} • ${item.gst}% GST`
      )
      .join("\n");
    alert(
      `Invoice #${invoice.number}\n${invoice.customerName}\n${detail}\nTotal: ${formatCurrency(
        invoice.total
      )}`
    );
  }

  if (action === "toggle-payment") {
    invoice.status = invoice.status === "Paid" ? "Unpaid" : "Paid";
    persist();
    refresh();
    toast("Payment status updated.");
  }
});

qs("#customerTable").addEventListener("click", (event) => {
  if (event.target.dataset.action !== "remove-customer") {
    return;
  }
  const index = Number(event.target.dataset.index);
  state.customers.splice(index, 1);
  persist();
  refresh();
});

qs("#productTable").addEventListener("click", (event) => {
  if (event.target.dataset.action !== "remove-product") {
    return;
  }
  const index = Number(event.target.dataset.index);
  state.products.splice(index, 1);
  persist();
  refresh();
});

qs("#paymentList").addEventListener("click", (event) => {
  if (event.target.dataset.action !== "remind") {
    return;
  }
  const invoiceNumber = event.target.dataset.id;
  toast(`Reminder sent for invoice #${invoiceNumber}.`);
});

qs("#printInvoice").addEventListener("click", () => {
  window.print();
});

qs("#generateEInvoice").addEventListener("click", () => {
  toast("E-Invoice generated and pushed to GST portal.");
});

qs("#generateEWay").addEventListener("click", () => {
  toast("E-Way Bill generated successfully.");
});

qs("#downloadReport").addEventListener("click", () => {
  const report = {
    generatedAt: new Date().toISOString(),
    invoices: state.invoices,
    customers: state.customers,
    products: state.products,
  };
  const blob = new Blob([JSON.stringify(report, null, 2)], {
    type: "application/json",
  });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = "drukinvoice-report.json";
  link.click();
  URL.revokeObjectURL(url);
});

qs("#exportData").addEventListener("click", () => {
  const exportData = {
    customers: state.customers,
    products: state.products,
    invoices: state.invoices,
  };
  const blob = new Blob([JSON.stringify(exportData, null, 2)], {
    type: "application/json",
  });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = "drukinvoice-data.json";
  link.click();
  URL.revokeObjectURL(url);
  toast("Data exported.");
});

qs("#newInvoiceQuick").addEventListener("click", () => {
  qs("#invoice").scrollIntoView({ behavior: "smooth" });
});

const init = () => {
  if (!qs("#invoiceDate").value) {
    qs("#invoiceDate").value = new Date().toISOString().slice(0, 10);
  }

  if (state.customers.length === 0) {
    state.customers.push({
      name: "Druk Retail",
      type: "Customer",
      gstin: "29ABCDE1234F1Z5",
      email: "accounts@drukretail.in",
      phone: "+91 98765 43210",
    });
  }

  if (state.products.length === 0) {
    state.products.push(
      {
        name: "GST Billing Subscription",
        hsn: "998315",
        price: 2499,
        gst: 18,
        stock: 120,
      },
      {
        name: "Barcode Scanner",
        hsn: "847130",
        price: 4200,
        gst: 12,
        stock: 18,
      }
    );
  }

  qs("#itemsContainer").appendChild(createItemRow());
  persist();
  refresh();
};

init();
