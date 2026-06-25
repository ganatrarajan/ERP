import 'dart:io';
import 'package:path_provider/path_provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:open_file/open_file.dart';
import '../network/dio_client.dart';

class DownloadService {
  static final DownloadService _instance = DownloadService._internal();
  factory DownloadService() => _instance;
  DownloadService._internal();

  final DioClient _dioClient = DioClient();

  // Requests storage permissions if needed (primarily older Android versions)
  Future<bool> _requestPermission() async {
    if (Platform.isAndroid) {
      // For Android 13 (API 33) and above, WRITE_EXTERNAL_STORAGE is obsolete
      // We write to app-specific directories, which don't require runtime permissions.
      // But if we wanted to save to public downloads, we would check manageExternalStorage or similar.
      // Writing to getApplicationDocumentsDirectory() works out-of-the-box.
      return true;
    }
    return true;
  }

  // Get local file path for storage
  Future<String> _getLocalPath(String filename) async {
    final directory = Platform.isAndroid
        ? await getExternalStorageDirectory() ?? await getApplicationDocumentsDirectory()
        : await getApplicationDocumentsDirectory();
    return '${directory.path}/$filename';
  }

  // Downloads a PDF from the given URL and saves it. Returns the file path.
  Future<String?> downloadFile({
    required String url,
    required String filename,
    void Function(int received, int total)? onProgress,
  }) async {
    try {
      final hasPermission = await _requestPermission();
      if (!hasPermission) {
        throw Exception("Storage permission denied");
      }

      final savePath = await _getLocalPath(filename);
      
      // Perform Dio download
      await _dioClient.download(
        url,
        savePath,
        onReceiveProgress: onProgress,
      );

      return savePath;
    } catch (e) {
      print("Download error: $e");
      rethrow;
    }
  }

  // Checks if a file is already downloaded
  Future<bool> isFileDownloaded(String filename) async {
    final savePath = await _getLocalPath(filename);
    final file = File(savePath);
    return await file.exists();
  }

  // Get local file instance
  Future<File> getLocalFile(String filename) async {
    final savePath = await _getLocalPath(filename);
    return File(savePath);
  }

  // Open the file using default viewer
  Future<void> openDownloadedFile(String filename) async {
    final savePath = await _getLocalPath(filename);
    final file = File(savePath);
    if (await file.exists()) {
      final result = await OpenFile.open(savePath);
      if (result.type != ResultType.done) {
        throw Exception("Could not open file: ${result.message}");
      }
    } else {
      throw Exception("File does not exist locally. Please download first.");
    }
  }

  // Share the file
  Future<void> shareDownloadedFile(String filename, String subject) async {
    final savePath = await _getLocalPath(filename);
    final file = File(savePath);
    if (await file.exists()) {
      final xFile = XFile(savePath);
      await Share.shareXFiles([xFile], subject: subject);
    } else {
      throw Exception("File does not exist locally. Please download first.");
    }
  }

  // Export to public downloads (Mobile Storage)
  Future<void> exportToPublicDownloads(String filename) async {
    final savePath = await _getLocalPath(filename);
    final file = File(savePath);
    if (!await file.exists()) {
      throw Exception("File does not exist locally. Please download first.");
    }

    if (Platform.isAndroid) {
      try {
        final publicDir = Directory('/storage/emulated/0/Download');
        if (!await publicDir.exists()) {
          throw Exception("Downloads directory not found");
        }
        
        // Add timestamp to prevent "File exists" permission denied errors on Android 10+
        final timestamp = DateTime.now().millisecondsSinceEpoch;
        final nameParts = filename.split('.');
        final ext = nameParts.length > 1 ? '.${nameParts.last}' : '';
        final baseName = nameParts.length > 1 ? nameParts.sublist(0, nameParts.length - 1).join('.') : filename;
        final uniqueFilename = '${baseName}_$timestamp$ext';
        
        final publicPath = '${publicDir.path}/$uniqueFilename';
        
        // Use writeAsBytes instead of copy to bypass some Scoped Storage restrictions when creating new files
        final bytes = await file.readAsBytes();
        await File(publicPath).writeAsBytes(bytes);
        
      } catch (e) {
        // If standard file IO fails, fallback to Share dialog
        await shareDownloadedFile(filename, "Save Document");
      }
    } else {
      // For iOS, just trigger the share dialog to let the user save it
      await shareDownloadedFile(filename, "Export Document");
    }
  }
}
