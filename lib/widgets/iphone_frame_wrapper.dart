import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;

class IPhoneFrameWrapper extends StatelessWidget {
  final Widget child;

  const IPhoneFrameWrapper({super.key, required this.child});

  @override
  Widget build(BuildContext context) {
    final isDesktop = kIsWeb && MediaQuery.of(context).size.width > 800;

    if (!isDesktop) return child;

    return Scaffold(
      backgroundColor: const Color(0xffddeefc),
      body: Center(
        child: Container(
          width: 340,
          height: 660,
          child: Stack(
            alignment: Alignment.topCenter,
            children: [
              // iPhone-Rahmen
              Positioned(
                top: 0,
                left: 0,
                right: 0,
                bottom: 0,
                child: Image.asset(
                  'iphone16.png',
                  fit: BoxFit.contain,
                ),
              ),
              // Content mit Hintergrundbild
              Positioned(
                top: 35,
                left: 20,
                right: 20,
                bottom: 40,
                child: Stack(
                  children: [
                    // Hintergrundbild
                    Positioned.fill(
                      child: Transform.scale(
                        scale: 0.92,
                        child: Image.asset(
                          'bg_login.jpg',
                          fit: BoxFit.cover,
                        ),
                      ),
                    ),
                    // Content
                    Transform.scale(
                      scale: 0.92,
                      child: child,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
