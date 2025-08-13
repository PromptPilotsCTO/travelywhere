import 'dart:async';
import 'package:flutter/material.dart';
import 'package:photo_view/photo_view.dart';
import 'package:photo_view/photo_view_gallery.dart';
import 'package:audioplayers/audioplayers.dart';

class GalleryScreen extends StatefulWidget {
  final List<String> imageUrls;
  final int initialIndex;

  const GalleryScreen({
    Key? key,
    required this.imageUrls,
    this.initialIndex = 0,
  }) : super(key: key);

  @override
  _GalleryScreenState createState() => _GalleryScreenState();
}

class _GalleryScreenState extends State<GalleryScreen> {
  late PageController _pageController;
  Timer? _slideshowTimer;
  final _audioPlayer = AudioPlayer();

  @override
  void initState() {
    super.initState();
    _pageController = PageController(initialPage: widget.initialIndex);
    _playMusic();
    if (widget.imageUrls.length > 1) {
      _startSlideshow();
    }
  }

  @override
  void dispose() {
    _slideshowTimer?.cancel();
    _pageController.dispose();
    _audioPlayer.dispose();
    super.dispose();
  }

  void _playMusic() {
    // Spielt die Musik von der angegebenen URL ab und stellt sie auf Endlosschleife ein.
    _audioPlayer.play(UrlSource('https://travelywhere.com/music/life.mp3'));
    _audioPlayer.setReleaseMode(ReleaseMode.loop);
  }

  void _startSlideshow() {
    _slideshowTimer?.cancel();
    _slideshowTimer = Timer.periodic(const Duration(seconds: 3), (timer) {
      if (!_pageController.hasClients) return;
      
      int nextPage = _pageController.page!.round() + 1;
      if (nextPage >= widget.imageUrls.length) {
        nextPage = 0;
      }
      _pageController.animateToPage(
        nextPage,
        duration: const Duration(milliseconds: 400),
        curve: Curves.easeInOut,
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      appBar: AppBar(
        backgroundColor: Colors.black,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: GestureDetector(
        onTapDown: (_) {
          _slideshowTimer?.cancel();
          _audioPlayer.pause();
        },
        onTapUp: (_) {
           _startSlideshow();
           _audioPlayer.resume();
        },
        child: PhotoViewGallery.builder(
          pageController: _pageController,
          itemCount: widget.imageUrls.length,
          onPageChanged: (index) {
            if (widget.imageUrls.length > 1) {
              _startSlideshow();
            }
          },
          builder: (context, index) {
            return PhotoViewGalleryPageOptions(
              imageProvider: NetworkImage(widget.imageUrls[index]),
              minScale: PhotoViewComputedScale.contained * 0.8,
              maxScale: PhotoViewComputedScale.covered * 2,
              heroAttributes: PhotoViewHeroAttributes(tag: widget.imageUrls[index]),
            );
          },
          scrollPhysics: const BouncingScrollPhysics(),
          backgroundDecoration: const BoxDecoration(
            color: Colors.black,
          ),
          loadingBuilder: (context, event) => const Center(
            child: SizedBox(
              width: 20.0,
              height: 20.0,
              child: CircularProgressIndicator(),
            ),
          ),
        ),
      ),
    );
  }
}
