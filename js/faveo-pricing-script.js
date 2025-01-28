document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('pricing-toggle');
    const products = document.querySelectorAll(".product-container");
    const groupId = document.body.getAttribute('data-group-id'); // Assuming group ID is stored in body

    // Initialize toggle state based on query parameter
    const urlParams = new URLSearchParams(window.location.search);
    const pricingType = urlParams.get('pricing');
    if (pricingType === 'yearly') {
        toggle.checked = true;
    } else {
        toggle.checked = false;
    }

    // Function to update product display based on toggle state
    function updateProductDisplay() {
        products.forEach(function(product) {
            var monthlyPriceElement = product.querySelector(".product-pricing h2");
            var yearlyPrice = parseFloat(monthlyPriceElement.getAttribute("data-yearly-price"));
            
           
                if (toggle.checked) {
                    // When toggle is enabled, show products with days=366 and calculate monthly price
                    if (product.dataset.days == "366") {
                        var monthlyPrice = (yearlyPrice / 12).toFixed(2);
                        monthlyPriceElement.textContent = currencySymbol + monthlyPrice;
                        product.style.display = "block"; // Ensure the product is visible
                    } else {
                        product.style.display = "none"; // Hide other products
                    }
                } else {
                    // When toggle is disabled, show products with days=30 and hide others
                    if (product.dataset.days == "30") {
                        monthlyPriceElement.textContent = monthlyPriceElement.getAttribute("data-monthly-price");
                        product.style.display = "block"; // Ensure the product is visible
                    } else {
                        product.style.display = "none"; // Hide other products
                    }
                }
         
        });
    }

    // Add event listener for toggle change
    toggle.addEventListener('change', function() {
        const pricingType = toggle.checked ? 'yearly' : 'monthly';
        // Reload the page with the selected pricing type as a query parameter
        window.location.search = '?pricing=' + pricingType;
    });

    // Call the updateProductDisplay function on page load
    updateProductDisplay();
});
