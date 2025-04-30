// login.js
document.getElementById("loginForm").addEventListener("submit", async (e) => {
  e.preventDefault();

  const benutzername = document.getElementById("benutzername").value.trim();
  const password = document.getElementById("password").value.trim();

  try {
    const response = await fetch("api/login.php", {
      method: "POST",
      // credentials: 'include', // uncomment if front-end & back-end are on different domains
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ benutzername, password }),
    });
    const result = await response.json();

    if (result.status === "success") {
      alert("Login erfolgreich!");
      window.location.href = "protected.html";
    } else {
      alert(result.message || "Login fehlgeschlagen.");
    }
  } catch (error) {
    console.error("Fehler:", error);
    alert("Etwas ist schiefgelaufen!");
  }
});
