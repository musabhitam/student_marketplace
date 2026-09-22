function validateLogin() {
    let email = document.forms["loginForm"]["email"].value;
    if (email == "") {
        alert("Email is required");
        return false;
    }
}

function validateItem() {
    let price = document.forms["itemForm"]["price"].value;

    if (price == "" || isNaN(price)) {
        alert("Enter valid price");
        return false;
    }
}

// SEARCH FUNCTION
function searchItem() {
    let input = document.getElementById("search").value.toLowerCase();
    let cards = document.getElementsByClassName("card");

    for (let i = 0; i < cards.length; i++) {
        let title = cards[i].innerText.toLowerCase();
        cards[i].style.display = title.includes(input) ? "" : "none";
    }
}