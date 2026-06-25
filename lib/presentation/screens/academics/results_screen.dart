import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../providers/result_provider.dart';
import '../../../data/models/exam_result_model.dart';
import '../../widgets/loading_view.dart';
import '../../widgets/error_view.dart';
import '../../widgets/empty_view.dart';

class ResultsScreen extends StatefulWidget {
  const ResultsScreen({super.key});

  @override
  State<ResultsScreen> createState() => _ResultsScreenState();
}

class _ResultsScreenState extends State<ResultsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<ResultProvider>().fetchResults();
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final resultProv = context.watch<ResultProvider>();

    return Scaffold(
      appBar: AppBar(
        title: const Text("Exam Results"),
      ),
      body: RefreshIndicator(
        onRefresh: () => resultProv.fetchResults(),
        child: _buildBody(resultProv, theme),
      ),
    );
  }

  Widget _buildBody(ResultProvider prov, ThemeData theme) {
    if (prov.isLoading && prov.results.isEmpty) {
      return const LoadingView(message: "Loading report sheets...");
    }

    if (prov.errorMessage != null && prov.results.isEmpty) {
      return ErrorView(
        message: prov.errorMessage!,
        onRetry: () => prov.fetchResults(),
      );
    }

    if (prov.results.isEmpty) {
      return const EmptyView(
        title: "No Results Published",
        description: "Official examination records will appear here once released by the administration.",
        icon: Icons.emoji_events_rounded,
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(16),
      itemCount: prov.results.length,
      separatorBuilder: (context, index) => const SizedBox(height: 12),
      itemBuilder: (context, index) {
        final result = prov.results[index];
        final isPass = result.result.toLowerCase() == 'pass';

        return Card(
          clipBehavior: Clip.antiAlias,
          margin: EdgeInsets.zero,
          child: ExpansionTile(
            title: Text(
              result.examName,
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            subtitle: Text(
              "Rank: #${result.rank ?? 'N/A'}  •  Percentage: ${result.percentage.toStringAsFixed(1)}%",
              style: theme.textTheme.bodyMedium,
            ),
            trailing: Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: (isPass ? Colors.green : Colors.red).withOpacity(0.12),
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                result.result.toUpperCase(),
                style: TextStyle(
                  color: isPass ? Colors.green : Colors.red,
                  fontWeight: FontWeight.bold,
                  fontSize: 11,
                ),
              ),
            ),
            childrenPadding: const EdgeInsets.all(16),
            children: [
              // Statistics Row
              Wrap(
                alignment: WrapAlignment.spaceAround,
                spacing: 16.0,
                runSpacing: 16.0,
                children: [
                  _buildResultMiniSummary("Obtained", "${result.totalObtainedMarks} / ${result.totalMaxMarks}", theme),
                  _buildResultMiniSummary("Grade", result.grade, theme),
                  _buildResultMiniSummary("Schedule", "${result.startDate} to \n${result.endDate}", theme),
                ],
              ),
              const SizedBox(height: 20),
              const Divider(),
              const SizedBox(height: 12),
              
              // Subject Details Table
              _buildSubjectDetailsTable(result.subjectDetails, theme),
            ],
          ),
        );
      },
    );
  }

  Widget _buildResultMiniSummary(String label, String value, ThemeData theme) {
    return Column(
      children: [
        Text(label, style: theme.textTheme.bodyMedium?.copyWith(fontSize: 11), textAlign: TextAlign.center),
        const SizedBox(height: 2),
        Text(value, style: theme.textTheme.bodyLarge?.copyWith(fontWeight: FontWeight.bold), textAlign: TextAlign.center),
      ],
    );
  }

  Widget _buildSubjectDetailsTable(List<SubjectResultDetail> details, ThemeData theme) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: DataTable(
        headingRowHeight: 40,
        dataRowMaxHeight: 48,
        columnSpacing: 20,
        horizontalMargin: 8,
        columns: const [
          DataColumn(label: Text("Subject", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
          DataColumn(label: Text("Marks", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
          DataColumn(label: Text("Grade", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
          DataColumn(label: Text("Status", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
        ],
        rows: details.map((subj) {
          final isSubPass = subj.isPass;
          return DataRow(
            cells: [
              DataCell(
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(subj.subjectName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                    Text(subj.subjectCode, style: TextStyle(color: theme.textTheme.bodyMedium?.color?.withOpacity(0.6), fontSize: 10)),
                  ],
                ),
              ),
              DataCell(
                Text(
                  "${subj.obtainedMarks.toStringAsFixed(0)} / ${subj.maxMarks.toStringAsFixed(0)}",
                  style: const TextStyle(fontSize: 12),
                ),
              ),
              DataCell(
                Text(
                  subj.grade,
                  style: const TextStyle(fontSize: 12),
                ),
              ),
              DataCell(
                Text(
                  isSubPass ? "Pass" : "Fail",
                  style: TextStyle(
                    color: isSubPass ? Colors.green : Colors.red,
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          );
        }).toList(),
      ),
    );
  }
}
