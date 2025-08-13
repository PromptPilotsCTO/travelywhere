import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';

class ShareTriplogsScreen extends StatefulWidget {
  const ShareTriplogsScreen({Key? key}) : super(key: key);

  @override
  _ShareTriplogsScreenState createState() => _ShareTriplogsScreenState();
}

class _ShareTriplogsScreenState extends State<ShareTriplogsScreen> {
  final _emailController = TextEditingController();
  List<dynamic> _connections = [];
  bool _isLoading = true;
  String? _errorMessage;
  int? _currentUserId;
  String? _currentUserFirstname;

  @override
  void initState() {
    super.initState();
    _loadCurrentUserAndConnections();
  }

  Future<void> _loadCurrentUserAndConnections() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      _currentUserId = prefs.getInt('user_id');
      _currentUserFirstname = prefs.getString('firstname');

      if (_currentUserId == null || _currentUserFirstname == null) {
        throw Exception('Benutzer nicht angemeldet oder Vorname nicht gefunden.');
      }
      await _loadConnections();
    } catch (e) {
      setState(() {
        _errorMessage = e.toString();
        _isLoading = false;
      });
    }
  }

  Future<void> _loadConnections() async {
    if (_currentUserId == null) return;
    setState(() {
      _isLoading = true;
    });
    try {
      final connections = await ApiService.getConnections(_currentUserId!);
      setState(() {
        _connections = connections;
        _isLoading = false;
        _errorMessage = null;
      });
    } catch (e) {
      setState(() {
        _errorMessage = 'Fehler beim Laden der Verbindungen: $e';
        _isLoading = false;
      });
    }
  }

  Future<void> _sendInvitation() async {
    if (_emailController.text.isEmpty || _currentUserId == null || _currentUserFirstname == null) return;

    try {
      await ApiService.inviteFriend(
        inviterUserId: _currentUserId!,
        inviterFirstname: _currentUserFirstname!,
        inviteeEmail: _emailController.text,
      );
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Einladung erfolgreich gesendet!'), backgroundColor: Colors.green),
      );
      _emailController.clear();
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Fehler: $e'), backgroundColor: Colors.red),
      );
    }
  }

  Future<void> _removeConnection(int connectionId) async {
    if (_currentUserId == null) return;

    try {
      await ApiService.removeConnection(_currentUserId!, connectionId);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Verbindung erfolgreich entfernt.'), backgroundColor: Colors.green),
      );
      await _loadConnections(); // Liste neu laden
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Fehler: $e'), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Share Triplogs'),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Invite a friend to see your triplogs.', style: TextStyle(fontSize: 16)),
            const SizedBox(height: 16),
            TextField(
              controller: _emailController,
              decoration: const InputDecoration(
                labelText: 'Friend\'s E-Mail',
                border: OutlineInputBorder(),
              ),
              keyboardType: TextInputType.emailAddress,
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _sendInvitation,
              child: const Text('Send Invitation'),
              style: ElevatedButton.styleFrom(
                minimumSize: const Size(double.infinity, 50),
              ),
            ),
            const SizedBox(height: 32),
            const Text('Your connections:', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            _buildConnectionsList(),
          ],
        ),
      ),
    );
  }

  Widget _buildConnectionsList() {
    if (_isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_errorMessage != null) {
      return Center(child: Text(_errorMessage!, style: const TextStyle(color: Colors.red)));
    }

    if (_connections.isEmpty) {
      return const Center(child: Text('You have no connections yet.'));
    }

    return Expanded(
      child: ListView.builder(
        itemCount: _connections.length,
        itemBuilder: (context, index) {
          final connection = _connections[index];
          return Card(
            margin: const EdgeInsets.symmetric(vertical: 4.0),
            color: Colors.green.shade100,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            child: ListTile(
              title: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(connection['firstname'] ?? 'No Name', style: const TextStyle(fontWeight: FontWeight.bold)),
                  Text(connection['email'] ?? 'No E-Mail', style: const TextStyle(fontSize: 12)),
                ],
              ),
              trailing: IconButton(
                iconSize: 20.0,
                padding: const EdgeInsets.only(left: 16.0), 
                icon: const Icon(Icons.close),
                onPressed: () => _removeConnection(connection['id']),
              ),
            ),
          );
        },
      ),
    );
  }
}
