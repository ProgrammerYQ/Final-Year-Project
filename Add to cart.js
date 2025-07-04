document.addEventListener('DOMContentLoaded', () => {
    const cartIcon = document.getElementById('cart-icon');
    const cart = document.querySelector('.cart');
    const cartClose = document.getElementById('cart-close');
    const addCartButtons = document.querySelectorAll('.add-cart');
    const cartContent = document.querySelector('.cart-content');
    const totalPriceElement = document.querySelector('.total-price');
    const cartItemCount = document.querySelector('.cart-item-count');

    cartIcon.onclick = () => {
        cart.classList.add('active');
    };

    cartClose.onclick = () => {
        cart.classList.remove('active');
    };

    addCartButtons.forEach((btn, index) => {
        btn.addEventListener('click', () => {
            const productBox = btn.closest('.product-box');
            const title = productBox.querySelector('.product-title').innerText;
            const price = productBox.querySelector('.price').innerText;
            const imageSrc = productBox.querySelector('img').src;

            addToCart(title, price, imageSrc);
            updateTotal();
        });
    });

    function addToCart(title, price, imageSrc) {
        const cartItems = cartContent.getElementsByClassName('cart-product-title');
        for (let i = 0; i < cartItems.length; i++) {
            if (cartItems[i].innerText === title) {
                alert('This item is already in the cart');
                return;
            }
        }

        const cartBox = document.createElement('div');
        cartBox.classList.add('cart-box');
        cartBox.innerHTML = `
            <img src="${imageSrc}" class="cart-img">
            <div class="cart-detail">
                <h2 class="cart-product-title">${title}</h2>
                <span class="cart-price">${price}</span>
                <div class="cart-quantity">
                    <button class="decrement">-</button>
                    <span class="number">1</span>
                    <button class="increment">+</button>
                </div>
            </div>
            <i class="ri-delete-bin-line cart-remove"></i>
        `;
        cartContent.appendChild(cartBox);
        updateCartCount();

    
        cartBox.querySelector('.cart-remove').onclick = () => {
            cartBox.remove();
            updateTotal();
            updateCartCount();
        };

    
        const incrementBtn = cartBox.querySelector('.increment');
        const decrementBtn = cartBox.querySelector('.decrement');
        const quantityDisplay = cartBox.querySelector('.number');

        incrementBtn.onclick = () => {
            let quantity = parseInt(quantityDisplay.innerText);
            quantity++;
            quantityDisplay.innerText = quantity;
            updateTotal();
        };

        decrementBtn.onclick = () => {
            let quantity = parseInt(quantityDisplay.innerText);
            if (quantity > 1) {
                quantity--;
                quantityDisplay.innerText = quantity;
                updateTotal();
            }
        };
    }

    function updateTotal() {
        let total = 0;
        const cartBoxes = cartContent.getElementsByClassName('cart-box');
        for (let box of cartBoxes) {
            const priceElement = box.querySelector('.cart-price');
            const quantity = parseInt(box.querySelector('.number').innerText);
            const price = parseFloat(priceElement.innerText.replace('RM ', ''));
            total += price * quantity;
        }
        totalPriceElement.innerText = `RM ${total.toFixed(2)}`;
    }

    function updateCartCount() {
        const count = cartContent.getElementsByClassName('cart-box').length;
        cartItemCount.innerText = count;
        cartItemCount.style.visibility = count > 0 ? 'visible' : 'hidden';
    }
});

function previewImage(event, previewId) {
  const reader = new FileReader();
  reader.onload = function () {
    const output = document.getElementById(previewId);
    output.src = reader.result;
  };
  reader.readAsDataURL(event.target.files[0]);
}
