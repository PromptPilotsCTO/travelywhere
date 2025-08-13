import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:geolocator/geolocator.dart';
import '../services/api_service.dart';

class AddTriplogScreen extends StatefulWidget {
  final int userId;
  const AddTriplogScreen({Key? key, required this.userId}) : super(key: key);

  @override
  _AddTriplogScreenState createState() => _AddTriplogScreenState();
}

class _AddTriplogScreenState extends State<AddTriplogScreen> {
  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _textController = TextEditingController();
  final List<XFile> _pickedImages = [];
  final List<Uint8List> _imageBytesList = [];
  final ImagePicker _picker = ImagePicker();
  bool _isLoading = false;

  Future<void> _pickImages() async {
    final pickedFiles = await _picker.pickMultiImage();
    if (pickedFiles.isNotEmpty) {
      for (var file in pickedFiles) {
        _pickedImages.add(file);
        _imageBytesList.add(await file.readAsBytes());
      }
      setState(() {});
    }
  }

  Future<void> _pickImageFromCamera() async {
    final pickedFile = await _picker.pickImage(source: ImageSource.camera);
    if (pickedFile != null) {
      _pickedImages.add(pickedFile);
      _imageBytesList.add(await pickedFile.readAsBytes());
      setState(() {});
    }
  }

  void _deleteImage(int index) {
    setState(() {
      _pickedImages.removeAt(index);
      _imageBytesList.removeAt(index);
    });
  }

  Future<Position?> _getCurrentPosition() async {
    try {
      return await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
    } catch (e) {
      print("Fehler beim Abrufen der Position: $e");
      return null;
    }
  }

  Future<void> _saveTriplog() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    Position? position;
    if (_pickedImages.isNotEmpty) {
      try {
        position = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Fehler: GPS-Position konnte nicht ermittelt werden. Bitte Berechtigungen prüfen und HTTPS verwenden. Details: $e')),
          );
        }
        setState(() => _isLoading = false);
        return; // Abbruch, wenn Position benötigt wird, aber nicht verfügbar ist
      }
    }

    try {
      // Schritt 1: Triplog erstellen
      final triplogRes = await ApiService.createTriplog(
        userId: widget.userId,
        title: _titleController.text,
        rememberText: _textController.text,
      );

      if (triplogRes['success'] != true) {
        throw Exception('Fehler beim Erstellen des Triplogs: ${triplogRes['message']}');
      }

      final triplogId = triplogRes['triplog_id'];

      // Schritt 2: Alle Bilder hochladen und auf Abschluss warten
      if (_pickedImages.isNotEmpty) {
        final uploadFutures = _pickedImages.map((imageFile) {
          return ApiService.uploadTriplogMedia(
            triplogId: triplogId,
            file: imageFile,
            fileType: 'image',
            latitude: position?.latitude,
            longitude: position?.longitude,
          );
        }).toList();

        // Auf alle Uploads warten
        await Future.wait(uploadFutures);
      }

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Triplog erfolgreich gespeichert!')),
        );
        await Future.delayed(const Duration(milliseconds: 100));
        Navigator.of(context).pop(true);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Fehler beim Speichern: $e')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    const Color primaryTextColor = Color(0xFF404040);
    const Color buttonColor = Color(0xFF8E0000);
    final topPadding = MediaQuery.of(context).padding.top;

    return Scaffold(
      body: Stack(
        children: [
          Container(
            decoration: const BoxDecoration(
              image: DecorationImage(
                image: AssetImage('assets/bg_login.jpg'),
                fit: BoxFit.cover,
              ),
            ),
          ),
          Padding(
            padding: EdgeInsets.only(
              top: topPadding + 10,
              left: 16.0,
              right: 16.0,
            ),
            child: SingleChildScrollView(
              child: Container(
                padding: const EdgeInsets.symmetric(vertical: 8.0, horizontal: 16.0),
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.8),
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          IconButton(
                            icon: const Icon(Icons.arrow_back, color: primaryTextColor),
                            onPressed: () => Navigator.of(context).pop(),
                          ),
                          const Text(
                            'Neues Triplog',
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: primaryTextColor),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _titleController,
                        style: const TextStyle(fontSize: 14),
                        decoration: InputDecoration(
                          labelText: 'Titel',
                          labelStyle: const TextStyle(fontSize: 14, color: primaryTextColor),
                          filled: true,
                          fillColor: Colors.white,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        ),
                        validator: (value) => value!.isEmpty ? 'Titel darf nicht leer sein' : null,
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _textController,
                        maxLines: 5,
                        style: const TextStyle(fontSize: 14),
                        decoration: InputDecoration(
                          labelText: 'Text',
                          labelStyle: const TextStyle(fontSize: 14, color: primaryTextColor),
                          filled: true,
                          fillColor: Colors.white,
                          border: OutlineInputBorder(borderRadius: BorderRadius.circular(8), borderSide: BorderSide.none),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        ),
                        validator: (value) => value!.isEmpty ? 'Text darf nicht leer sein' : null,
                      ),
                      const SizedBox(height: 16),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        children: [
                          IconButton(icon: const Icon(Icons.photo_library, color: buttonColor, size: 26), onPressed: _pickImages),
                          IconButton(icon: const Icon(Icons.camera_alt, color: buttonColor, size: 26), onPressed: _pickImageFromCamera),
                          IconButton(icon: const Icon(Icons.videocam, color: buttonColor, size: 26), onPressed: () {}),
                        ],
                      ),
                      if (_imageBytesList.isNotEmpty) ...[
                        const SizedBox(height: 12),
                        SizedBox(
                          height: 100,
                          child: ListView.builder(
                            scrollDirection: Axis.horizontal,
                            itemCount: _imageBytesList.length,
                            itemBuilder: (context, index) {
                              return Padding(
                                padding: const EdgeInsets.only(right: 8.0),
                                child: Stack(
                                  children: [
                                    ClipRRect(
                                      borderRadius: BorderRadius.circular(8),
                                      child: Image.memory(
                                        _imageBytesList[index],
                                        height: 100,
                                        width: 100,
                                        fit: BoxFit.contain,
                                      ),
                                    ),
                                    Positioned(
                                      top: 0,
                                      right: 0,
                                      child: GestureDetector(
                                        onTap: () => _deleteImage(index),
                                        child: Container(
                                          decoration: const BoxDecoration(
                                            color: Colors.black54,
                                            shape: BoxShape.circle,
                                          ),
                                          child: const Icon(Icons.close, color: Colors.white, size: 18),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                          ),
                        ),
                      ],
                      const SizedBox(height: 16),
                      _isLoading
                          ? const Center(child: CircularProgressIndicator())
                          : Center(
                              child: ElevatedButton(
                                onPressed: _saveTriplog,
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: buttonColor,
                                  foregroundColor: Colors.white,
                                  padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 10),
                                  textStyle: const TextStyle(fontSize: 14),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
                                ),
                                child: const Text('Speichern'),
                              ),
                            ),
                      const SizedBox(height: 16),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}