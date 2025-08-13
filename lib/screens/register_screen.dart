import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'login_screen.dart'; // Import für LoginScreen
import '../services/auth_service.dart'; // Import für AuthService

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({Key? key}) : super(key: key);

  @override
  _RegisterScreenState createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final AuthService _authService = AuthService();
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();
  final FocusNode _nameFocusNode = FocusNode();
  final FocusNode _emailFocusNode = FocusNode();
  final FocusNode _passwordFocusNode = FocusNode();

  bool _isLoading = false;

  @override
  void initState() {
    super.initState();
    _setupEnterKeyHandling();
  }

  void _setupEnterKeyHandling() {
    // Name-Feld: ENTER fokussiert E-Mail-Feld
    _nameFocusNode.addListener(() {
      if (_nameFocusNode.hasFocus) {
        // Wenn Name-Feld fokussiert wird, können wir hier zusätzliche Logik hinzufügen
      }
    });

    // E-Mail-Feld: ENTER fokussiert Passwort-Feld
    _emailFocusNode.addListener(() {
      if (_emailFocusNode.hasFocus) {
        // Wenn E-Mail-Feld fokussiert wird, können wir hier zusätzliche Logik hinzufügen
      }
    });

    // Passwort-Feld: ENTER startet Registrierung
    _passwordFocusNode.addListener(() {
      if (_passwordFocusNode.hasFocus) {
        // Wenn Passwort-Feld fokussiert wird, können wir hier zusätzliche Logik hinzufügen
      }
    });
  }

  Future<void> _register() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() {
      _isLoading = true;
    });

    final result = await _authService.registerUser(
      name: _nameController.text.trim(),
      email: _emailController.text.trim(),
      password: _passwordController.text.trim(),
    );

    setState(() {
      _isLoading = false;
    });

    if (mounted) { // Check if the widget is still in the tree
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message'] ?? 'Ein Fehler ist aufgetreten.')),
      );
      if (result['success'] == true) {
        // Navigate to login screen after showing the message
        // Adding a small delay so user can read the snackbar
        Future.delayed(const Duration(seconds: 2), () {
          if (mounted) {
             Navigator.of(context).pushReplacement(
              MaterialPageRoute(builder: (context) => const LoginScreen()),
            );
          }
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent, // Assuming this is for an overlay effect from iPhoneFrameWrapper
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20.0),
          child: Form(
            key: _formKey,
            child: Container(
              width: 280, // Consistent width from original design
              padding: const EdgeInsets.all(20), // Padding inside the form container
              // Optional: Add a background to the form container if it's not an overlay
              // decoration: BoxDecoration(
              //   color: Colors.white.withOpacity(0.9),
              //   borderRadius: BorderRadius.circular(12),
              //   boxShadow: [BoxShadow(color: Colors.black26, blurRadius: 10, offset: Offset(0,5))]\n              // ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: <Widget>[
                  const Text(
                    'Konto erstellen',
                    style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white), // Assuming white text for dark overlay
                  ),
                  const SizedBox(height: 25),
                  TextFormField(
                    controller: _nameController,
                    focusNode: _nameFocusNode,
                    style: const TextStyle(color: Colors.black87),
                    decoration: InputDecoration(
                      labelText: 'Name',
                      labelStyle: TextStyle(color: Colors.grey[700]),
                      filled: true,
                      fillColor: Colors.white,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      prefixIcon: Icon(Icons.person, color: Colors.grey[700]),
                    ),
                    textInputAction: TextInputAction.next, // ENTER geht zum nächsten Feld
                    onFieldSubmitted: (value) {
                      // Fokussiere E-Mail-Feld wenn ENTER gedrückt wird
                      FocusScope.of(context).requestFocus(_emailFocusNode);
                    },
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Bitte geben Sie Ihren Namen ein.';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _emailController,
                    focusNode: _emailFocusNode,
                    style: const TextStyle(color: Colors.black87),
                    decoration: InputDecoration(
                      labelText: 'E-Mail',
                      labelStyle: TextStyle(color: Colors.grey[700]),
                      filled: true,
                      fillColor: Colors.white,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      prefixIcon: Icon(Icons.email, color: Colors.grey[700]),
                    ),
                    keyboardType: TextInputType.emailAddress,
                    textInputAction: TextInputAction.next, // ENTER geht zum nächsten Feld
                    onFieldSubmitted: (value) {
                      // Fokussiere Passwort-Feld wenn ENTER gedrückt wird
                      FocusScope.of(context).requestFocus(_passwordFocusNode);
                    },
                    validator: (value) {
                      if (value == null || value.trim().isEmpty) {
                        return 'Bitte geben Sie Ihre E-Mail ein.';
                      }
                      if (!value.contains('@') || !value.contains('.')) {
                        return 'Bitte geben Sie eine gültige E-Mail ein.';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _passwordController,
                    focusNode: _passwordFocusNode,
                    style: const TextStyle(color: Colors.black87),
                    decoration: InputDecoration(
                      labelText: 'Passwort',
                      labelStyle: TextStyle(color: Colors.grey[700]),
                      filled: true,
                      fillColor: Colors.white,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                      prefixIcon: Icon(Icons.lock, color: Colors.grey[700]),
                    ),
                    obscureText: true,
                    textInputAction: TextInputAction.done, // ENTER startet Registrierung
                    onFieldSubmitted: (value) {
                      // Starte Registrierung wenn ENTER gedrückt wird
                      if (!_isLoading) {
                        _register();
                      }
                    },
                    validator: (value) {
                      if (value == null || value.isEmpty) {
                        return 'Bitte geben Sie ein Passwort ein.';
                      }
                      if (value.length < 6) {
                        return 'Das Passwort muss mindestens 6 Zeichen lang sein.';
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 24),
                  _isLoading
                      ? const CircularProgressIndicator(valueColor: AlwaysStoppedAnimation<Color>(Colors.white),)
                      : ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF182146), // Using color from original button
                            padding: const EdgeInsets.symmetric(horizontal: 50, vertical: 15),
                            textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          ),
                          onPressed: _register,
                          child: const Text('Registrieren', style: TextStyle(color: Colors.white)),
                        ),
                  const SizedBox(height: 20),
                  TextButton(
                    onPressed: () {
                      // Navigate to LoginScreen
                      Navigator.of(context).pushReplacement(
                        MaterialPageRoute(builder: (context) => const LoginScreen()),
                      );
                    },
                    child: const Text(
                      'Bereits ein Konto? Einloggen',
                      style: TextStyle(color: Colors.white70, fontSize: 14),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _nameFocusNode.dispose();
    _emailFocusNode.dispose();
    _passwordFocusNode.dispose();
    super.dispose();
  }
}