document.addEventListener('DOMContentLoaded', function () {
  // Load data from the JSON file
  fetch('india-states-districts.json')
    .then(response => response.json())
    .then(statesAndDistricts => {
      // Populate states dropdown
      const stateDropdown = document.getElementById('state');
      Object.keys(statesAndDistricts).forEach(state => {
        const option = document.createElement('option');
        option.value = state;
        option.textContent = state;
        stateDropdown.appendChild(option);
      });

      // Populate districts based on the selected state
      stateDropdown.addEventListener('change', function () {
        const selectedState = this.value;
        const districtDropdown = document.getElementById('district');
        districtDropdown.innerHTML = '<option value="">Select District</option>'; // Reset districts dropdown

        if (statesAndDistricts[selectedState]) {
          statesAndDistricts[selectedState].forEach(district => {
            const option = document.createElement('option');
            option.value = district;
            option.textContent = district;
            districtDropdown.appendChild(option);
          });
        }
      });
    })
    .catch(error => console.error('Error loading JSON data:', error));



});

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("signupForm");
  const inputs = form.querySelectorAll("input");

  // Add validation listeners to all inputs
  inputs.forEach((input) => {
    input.addEventListener("input", async function () {
      await validateField(this);
      updateSubmitButton();
    });
  });
 
  // Form submission
  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    let isValid = true;
    for (const input of inputs) {
        if (!await validateField(input)) {
            isValid = false;
        }
    }

    if (isValid) {
        form.submit();
    }
});
});

async function validateField(input) {
  let isValid = true;
  const errorElement = input.nextElementSibling;

  // Reset classes and error message
  input.classList.remove("valid", "invalid");
  errorElement.textContent = "";

  const value = input.value.trim();

  switch (input.name) {
    case "firstName":
    case "lastName":
      isValid = validateName(value);
      if (!isValid) {
        errorElement.textContent =
          "Name should be 2-50 characters long and contain only letters";
      }
      break;

      case "email":
        isValid = validateEmail(value);
        if (!isValid) {
            errorElement.textContent = "Please enter a valid email address";
        } else {
            // Check if email exists
            const emailExists = await checkFieldExists('email', value);
            if (emailExists) {
                isValid = false;
                errorElement.textContent = "This email is already registered";
            }
        }
        break;

    case "mobileNumber":
        isValid = validateMobile(value);
        if (!isValid) {
            errorElement.textContent = "Please enter a valid 10-digit mobile number starting with 6-9";
        } else {
            // Check if mobile number exists
            const mobileExists = await checkFieldExists('mobileNumber', value);
            if (mobileExists) {
                isValid = false;
                errorElement.textContent = "This mobile number is already registered";
            }
        }
        break;

    case "streetAddress":
      isValid = validateAddress(value);
      if (!isValid) {
        errorElement.textContent = "Address should be 5-100 characters long";
      }
      break;

    case "city":
      isValid = validateGenericText(value);
      if (!isValid) {
        errorElement.textContent = "This field should be 2-50 characters long";
      }
      break;

    
      
    case "password":
      isValid = validatePassword(value);
      updatePasswordStrength(value);
      if (!isValid) {
        errorElement.textContent =
          "Password must be  8 characters with letters(lower and upper case), numbers and symbols";
      }
      break;


    case "confirmPassword":
      const password = document.getElementById("password").value;
      isValid = validateConfirmPassword(value, password);
      if (!isValid) {
        errorElement.textContent = "Passwords do not match";
      }
      break;
  }

  input.classList.add(isValid ? "valid" : "invalid");
  return isValid;
}

function validateName(value) {
  return /^[a-zA-Z\s]{2,50}$/.test(value);
}

function validateEmail(value) {
  return /^(?=[^@]*[a-zA-Z]{3,})[a-zA-Z0-9._%+-]+@[a-zA-Z0-9-]+(\.[a-zA-Z]{2,})+$/.test(value);
}

function validateMobile(value) {
  return /^[6-9]\d{9}$/.test(value);
}

function validateAddress(value) {
  return value.length >= 5 && value.length <= 100;
}

function validateGenericText(value) {
  return /^[a-zA-Z]+$/.test(value) && value.length >= 2 && value.length <= 50;
}




// Function to validate if the input is a 6-digit pincode


function validatePassword(value) {
  return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>])[A-Za-z\d!@#$%^&*(),.?":{}|<>]{8,}$/.test(
    value
  );
}

function validateConfirmPassword(confirmValue, passwordValue) {
  return confirmValue === passwordValue;
}

function updatePasswordStrength(password) {
  const strengthDiv = document.querySelector(".password-strength");
  strengthDiv.className = "password-strength";

  if (password.length === 0) {
    strengthDiv.style.width = "0";
    return;
  }

  let strength = 0;
  if (password.length >= 8) strength++;
  if (/[A-Z]/.test(password)) strength++;
  if (/[0-9]/.test(password)) strength++;
  if (/[^A-Za-z0-9]/.test(password)) strength++;

  switch (strength) {
    case 0:
    case 1:
      strengthDiv.classList.add("weak");
      break;
    case 2:
    case 3:
      strengthDiv.classList.add("medium");
      break;
    case 4:
      strengthDiv.classList.add("strong");
      break;
  }
}

async function updateSubmitButton() {
  const submitButton = document.querySelector('button[type="submit"]');
  const inputs = document.querySelectorAll("input");
  let isValid = true;

  inputs.forEach((input) => {
    if (input.classList.contains("invalid") || !input.value.trim()) {
      isValid = false;
    }
  });

  submitButton.disabled = !isValid;
}


let pincodeList = [];
let isDataLoaded = false;

// Load JSON file
fetch("pincode.json")
  .then(response => {
    if (!response.ok) {
      throw new Error("Network response was not ok");
    }
    return response.json();
  })
  .then(data => {
    pincodeList = data.pincodes.map(String); // Convert all pincodes to strings for consistent comparison
    isDataLoaded = true;
  })
  .catch(error => console.error("Error loading pincode data:", error));

document.addEventListener("DOMContentLoaded", function () {
  document.getElementById("pincode").addEventListener("input", function () {
    const inputPincode = this.value.trim();
    const errorMessage = this.nextElementSibling;

    // Reset message for empty input
    if (!inputPincode) {
      errorMessage.textContent = "";
      return;
    }

    if (!/^\d{6}$/.test(inputPincode)) {
      errorMessage.textContent = "Please enter a valid 6-digit pin";
      errorMessage.style.color = "red";
      return;
    }

    if (!isDataLoaded) {
      errorMessage.textContent = "Loading pincode data, please wait...";
      errorMessage.style.color = "orange";
      return;
    }

    if (pincodeList.includes(inputPincode)) {
      errorMessage.textContent = "Valid Pincode!";
      errorMessage.style.color = "green";
    } else {
      errorMessage.textContent = "Pincode not found in the database";
      errorMessage.style.color = "red";
    }
  });
});


async function checkFieldExists(field, value) {
  try {
      const response = await fetch(`check_exists.php?field=${field}&value=${value}`);
      const data = await response.json();
      return data.exists;
  } catch (error) {
      console.error('Error checking field:', error);
      return false;
  }
}