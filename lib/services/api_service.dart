import 'dart:convert';
import 'dart:io';
import 'dart:async';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:flutter/foundation.dart' show kIsWeb;

class ApiService {
  static const String baseUrl = 'https://travelywhere.com/api';
  
  // Timeout-Duration für alle Requests
  static const Duration timeout = Duration(seconds: 30);

  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      ).timeout(timeout);

      final responseBody = jsonDecode(response.body);

      if (response.statusCode == 200 && responseBody['success'] == true) {
        final prefs = await SharedPreferences.getInstance();
        // KORREKTUR: Die korrekte Datenstruktur verwenden (responseBody['data'])
        await prefs.setInt('user_id', responseBody['data']['user_id']);
        await prefs.setString('firstname', responseBody['data']['firstname']);
        return {
          'success': true,
          'data': responseBody['data'], // KORREKTUR: Das 'data'-Objekt zurückgeben
        };
      } else {
        return {
          'success': false,
          'message': responseBody['message'] ?? 'Unbekannter Fehler beim Login',
        };
      }
    } catch (e) {
      print('Fehler in ApiService.login: $e');
      return {'success': false, 'message': 'Netzwerk- oder Verarbeitungsfehler.'};
    }
  }

  static Future<Map<String, dynamic>> createTriplog({
    required int userId,
    required String title,
    required String rememberText,
  }) async {
    try {
      print('Sending triplog creation request...');
      final response = await http.post(
        Uri.parse('$baseUrl/create_triplog.php'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': userId,
          'title': title,
          'remember_text': rememberText,
        }),
      ).timeout(timeout);
      
      print('Response status: ${response.statusCode}');
      print('Response body: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        // Sicherstellen, dass triplog_id als Integer zurückgegeben wird
        int? triplogId;
        if (data['triplog_id'] != null) {
          triplogId = int.tryParse(data['triplog_id'].toString());
        }
        
        return {
          'success': data['success'], 
          'triplog_id': triplogId, 
          'message': data['message']
        };
      } else {
        return {'success': false, 'message': 'HTTP ${response.statusCode}: Fehler beim Erstellen des Triplogs'};
      }
    } catch (e) {
      print('Error in createTriplog: $e');
      if (e is http.ClientException) {
        return {'success': false, 'message': 'Verbindungsfehler: ${e.message}'};
      } else if (e is SocketException) {
        return {'success': false, 'message': 'Netzwerkfehler: Keine Verbindung zum Server'};
      } else if (e is TimeoutException) {
        return {'success': false, 'message': 'Timeout: Die Anfrage hat zu lange gedauert'};
      } else {
        return {'success': false, 'message': 'Unerwarteter Fehler: $e'};
      }
    }
  }

  static Future<Map<String, dynamic>> uploadTriplogMedia({
    required int triplogId,
    required XFile file,
    required String fileType, // 'image' oder 'video'
    double? latitude,
    double? longitude,
  }) async {
    try {
      print('Starting media upload for triplog_id: $triplogId (type: ${triplogId.runtimeType})');
      print('File path: ${file.path}');
      print('File type: $fileType');
      
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/upload_triplog_media.php'),
      );
      
      // Sicherstellen, dass triplog_id als String gesendet wird
      request.fields['triplog_id'] = triplogId.toString();
      request.fields['file_type'] = fileType;
      if (latitude != null) request.fields['gps_latitude'] = latitude.toString();
      if (longitude != null) request.fields['gps_longitude'] = longitude.toString();
      
      print('Request fields: ${request.fields}');
      
      // Plattform-spezifische Datei-Behandlung
      if (kIsWeb) {
        // Für Web: Verwende Uint8List aus XFile
        final bytes = await file.readAsBytes();
        request.files.add(http.MultipartFile.fromBytes(
          'file',
          bytes,
          filename: file.name, // Verwende file.name für Web
        ));
      } else {
        // Für Mobile: Verwende fromPath
        request.files.add(await http.MultipartFile.fromPath('file', file.path));
      }
      
      print('Sending upload request...');
      final streamedResponse = await request.send().timeout(timeout);
      final response = await http.Response.fromStream(streamedResponse);
      
      print('Upload response status: ${response.statusCode}');
      print('Upload response body: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {'success': data['success'], 'message': data['message']};
      } else {
        return {'success': false, 'message': 'HTTP ${response.statusCode}: Fehler beim Hochladen des Mediums'};
      }
    } catch (e) {
      print('Error in uploadTriplogMedia: $e');
      if (e is http.ClientException) {
        return {'success': false, 'message': 'Verbindungsfehler beim Upload: ${e.message}'};
      } else if (e is SocketException) {
        return {'success': false, 'message': 'Netzwerkfehler beim Upload: Keine Verbindung zum Server'};
      } else if (e is TimeoutException) {
        return {'success': false, 'message': 'Upload Timeout: Die Anfrage hat zu lange gedauert'};
      } else {
        return {'success': false, 'message': 'Unerwarteter Upload-Fehler: $e'};
      }
    }
  }

  static Future<List<dynamic>> getTriplogs(int userId) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/get_triplogs.php?user_id=$userId'),
      ).timeout(timeout);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        } else {
          throw Exception('Fehler beim Laden der Triplogs: ${data['message']}');
        }
      } else {
        throw Exception('Serverfehler: ${response.statusCode}');
      }
    } catch (e) {
      print('Fehler in getTriplogs: $e');
      throw Exception('Netzwerk- oder Serverfehler: $e');
    }
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('session_token');
  }

  static Future<Map<String, dynamic>> inviteFriend({
    required int inviterUserId,
    required String inviterFirstname,
    required String inviteeEmail,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/invite_friend.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'inviter_user_id': inviterUserId,
        'inviter_firstname': inviterFirstname,
        'invitee_email': inviteeEmail,
      }),
    );
    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else {
      return {'success': false, 'message': 'Server error: ${response.statusCode}'};
    }
  }

  static Future<Map<String, dynamic>> confirmInvitation({
    required String token,
    required int userId,
  }) async {
    final response = await http.post(
      Uri.parse('$baseUrl/confirm_invitation.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'token': token,
        'user_id': userId,
      }),
    );
    return jsonDecode(response.body);
  }

  static Future<void> logout() async {
    // Hier könnten Sie serverseitige Logout-Logik aufrufen, falls erforderlich
    // z.B. Token-Invalidierung
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('session_token');
    await prefs.remove('user_id');
    await prefs.remove('firstname');
  }

  static Future<void> resendConfirmationEmail(String email) async {
    final response = await http.post(
      Uri.parse('$baseUrl/resend_confirmation.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'email': email}),
    ).timeout(timeout);

    if (response.statusCode != 200) {
      throw Exception('Serverfehler: ${response.statusCode}');
    }

    final data = jsonDecode(response.body);
    if (data['success'] != true) {
      throw Exception('Fehler: ${data['message']}');
    }
  }

  static Future<List<dynamic>> getConnections(int userId) async {
    final response = await http.get(
      Uri.parse('$baseUrl/get_connections.php?user_id=$userId'),
    ).timeout(timeout);

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      if (data['success'] == true) {
        return data['data'];
      } else {
        throw Exception('Fehler beim Laden der Verbindungen: ${data['message']}');
      }
    } else {
      throw Exception('Serverfehler: ${response.statusCode}');
    }
  }

  static Future<void> removeConnection(int userId, int connectionId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/remove_connection.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'user_id': userId, 'connection_id': connectionId}),
    ).timeout(timeout);

    if (response.statusCode != 200) {
      throw Exception('Serverfehler: ${response.statusCode}');
    }

    final data = jsonDecode(response.body);
    if (data['success'] != true) {
      throw Exception('Fehler beim Entfernen der Verbindung: ${data['message']}');
    }
  }
}
