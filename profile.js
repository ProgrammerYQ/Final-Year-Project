document.getElementById('profileImage').addEventListener('click', () => {
  document.getElementById('profileImageInput').click();
});

document.getElementById('profileImageInput').addEventListener('change', (e) => {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function(evt) {
      document.getElementById('profileImage').src = evt.target.result;
    };
    reader.readAsDataURL(file);
  }
});

function saveProfile() {
  const name = document.getElementById('name').value;
  const description = document.getElementById('description').value;
  const contact = document.getElementById('contact').value;
  const address = document.getElementById('address').value;

  console.log("Saving Profile...");
  console.log({ name, description, contact, address });

  alert("Profile saved successfully!");
}
