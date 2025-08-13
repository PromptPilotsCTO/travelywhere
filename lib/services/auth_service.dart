import 'dart:convert'; // Für jsonEncode und jsonDecode
import 'package:http/http.dart' as http;

// Die alte User-Klasse und _users Liste werden nicht mehr für die Simulation benötigt.
// class User {
//   String name;
//   String email;
//   String password;
//   bool isVerified;
//
//   User({required this.name, required this.email, required this.password, this.isVerified = false});
// }

// Neue User-Klasse, die die Daten vom Server repräsentiert
class User {
  final int id;
  final String firstname;
  final String? lastname; // Kann null sein, je nach Datenbank
  final String? username; // Kann null sein, je nach Datenbank
  final String email;

  User({
    required this.id,
    required this.firstname,
    this.lastname,
    this.username,
    required this.email,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      firstname: json['firstname'] as String,
      lastname: json['lastname'] as String?,
      username: json['username'] as String?,
      email: json['email'] as String,
    );
  }
}

class AuthService {

  // Simulates user registration
  Future<Map<String, dynamic>> registerUser({
    required String name,
    required String email,
    required String password,
    String language = 'de', // Standardmäßig Deutsch, kann von der UI angepasst werden
  }) async {
    final url = Uri.parse('https://travelywhere.com/api/register_user.php'); // Ihre Backend-URL

    try {
      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json; charset=UTF-8',
        },
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
          'language': language, // Sprache an das Backend senden
        }),
      );

      if (response.statusCode == 200) {
        final responseData = jsonDecode(response.body);
        // Loggen der Server-Antwort für Debugging-Zwecke
        print('AuthService registerUser Response: $responseData'); 
        return responseData;
      } else {
        // Fehler vom Server (z.B. 400, 500)
        print('AuthService registerUser API Error: ${response.statusCode}');
        print('AuthService registerUser API Response Body: ${response.body}');
        // Versuchen, die Fehlermeldung vom Server zu parsen, falls vorhanden
        try {
          final errorData = jsonDecode(response.body);
          return {
            'success': false,
            'message': errorData['message'] ?? 'Serverfehler: ${response.statusCode}.'
          };
        } catch (e) {
          return {
            'success': false,
            'message': 'Serverfehler: ${response.statusCode}. Antwort nicht lesbar.'
          };
        }
      }
    } catch (e) {
      // Netzwerkfehler oder anderer Fehler beim Senden der Anfrage
      print('AuthService registerUser Network/Request Error: $e');
      return {
        'success': false,
        'message': 'Netzwerkfehler. Bitte überprüfen Sie Ihre Verbindung und versuchen Sie es erneut.'
      };
    }
  }

  // Simulates user login
  Future<Map<String, dynamic>> loginUser({
    required String email,
    required String password,
  }) async {
    final url = Uri.parse('https://travelywhere.com/api/login_user.php'); // Ihre Backend-URL für Login

    try {
      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/json; charset=UTF-8',
        },
        body: jsonEncode({
          'email': email,
          'password': password,
        }),
      );

      if (response.statusCode == 200) {
        final responseData = jsonDecode(response.body);
        // Loggen der Server-Antwort für Debugging-Zwecke
        print('AuthService loginUser Response: $responseData');
        return responseData; // Erwartet z.B. {'success': true, 'message': '...', 'user': {...}}
      } else {
        // Fehler vom Server (z.B. 400, 401, 500)
        print('AuthService loginUser API Error: ${response.statusCode}');
        print('AuthService loginUser API Response Body: ${response.body}');
        try {
          final errorData = jsonDecode(response.body);
          return {
            'success': false,
            'message': errorData['message'] ?? 'Serverfehler beim Login: ${response.statusCode}.'
          };
        } catch (e) {
          return {
            'success': false,
            'message': 'Serverfehler beim Login: ${response.statusCode}. Antwort nicht lesbar.'
          };
        }
      }
    } catch (e) {
      // Netzwerkfehler oder anderer Fehler beim Senden der Anfrage
      print('AuthService loginUser Network/Request Error: $e');
      return {
        'success': false,
        'message': 'Netzwerkfehler beim Login. Bitte überprüfen Sie Ihre Verbindung.'
      };
    }
  }

  // Simulates email verification (e.g., after user clicks a link in an email)
  // For testing, we might call this manually or from a debug option.
  Future<bool> verifyEmail(String email) async {
    // TODO: Implement backend call for email verification if needed client-side,
    // or remove if verification is purely server-driven via link.
    // try {
    //   final user = _users.firstWhere((u) => u.email == email);
    //   user.isVerified = true;
    //   print('AuthService: Email $email has been verified.');
    //   return true;
    // } catch (e) {
    //   print('AuthService: Could not verify email $email. User not found.');
    //   return false;
    // }
    print('AuthService: verifyEmail is not implemented for backend.');
    return false;
  }

  // Helper to get a user's verification status (for debugging/testing)
  static bool isUserVerified(String email) {
    // TODO: Implement backend call to check verification status if needed.
    // try {
    //   final user = _users.firstWhere((u) => u.email == email);
    //   return user.isVerified;
    // } catch (e) {
    //   return false;
    // }
    print('AuthService: isUserVerified is not implemented for backend.');
    return false;
  }

  // Helper to manually verify a user for testing purposes
  static void verifyUserForTesting(String email) {
    // TODO: This was for local simulation. For backend testing, manage via database or specific test API endpoint.
    //  try {
    //   final user = _users.firstWhere((u) => u.email == email);
    //   user.isVerified = true;
    //   print('AuthService (TESTING): Manually verified $email');
    // } catch (e) {
    //   print('AuthService (TESTING): Could not find user $email to verify.');
    // }
    print('AuthService (TESTING): verifyUserForTesting is not applicable for backend in this form.');
  }

  // Clear all users (for testing)
  static void clearUsersForTesting() {
    // TODO: This was for local simulation. For backend testing, manage via database or specific test API endpoint.
    // _users.clear();
    // print('AuthService (TESTING): All users cleared.');
    print('AuthService (TESTING): clearUsersForTesting is not applicable for backend in this form.');
  }
}
