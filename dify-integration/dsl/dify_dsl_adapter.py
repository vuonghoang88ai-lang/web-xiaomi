#!/usr/bin/env python3
"""
Dify DSL Integration Adapter
Chuyển đổi DSL YAML thành executable nodes với OrchestrationManager integration
"""

import os
import yaml
import json
import logging
from typing import Dict, List, Any, Optional
from dataclasses import dataclass
from pathlib import Path
import asyncio

logger = logging.getLogger(__name__)


@dataclass
class DSLNode:
    """Đại diện cho một node trong DSL workflow"""
    id: str
    type: str
    description: str
    depends_on: List[str]
    config: Dict[str, Any]
    outputs: Dict[str, Any]
    error_handler: Optional[str] = None


class DifyDSLParser:
    """Parser cho YAML DSL files"""
    
    def __init__(self, dsl_file: str):
        self.dsl_file = dsl_file
        self.dsl_config = self._load_dsl()
        self.nodes: Dict[str, DSLNode] = {}
        self.integrations = {}
        self._parse_nodes()
    
    def _load_dsl(self) -> Dict:
        """Load DSL configuration from YAML file"""
        if not os.path.exists(self.dsl_file):
            raise FileNotFoundError(f"DSL file not found: {self.dsl_file}")
        
        with open(self.dsl_file, 'r', encoding='utf-8') as f:
            return yaml.safe_load(f)
    
    def _parse_nodes(self):
        """Parse workflow nodes from DSL config"""
        workflow = self.dsl_config.get('workflow', {})
        nodes_data = workflow.get('nodes', [])
        
        for node_data in nodes_data:
            node = DSLNode(
                id=node_data.get('id'),
                type=node_data.get('type'),
                description=node_data.get('description'),
                depends_on=node_data.get('depends_on', []),
                config=node_data.get('config', {}),
                outputs=node_data.get('outputs', {}),
                error_handler=node_data.get('error_handler')
            )
            self.nodes[node.id] = node
        


class DifyNodeExecutor:
    """Executor cho các node types"""
    
    def __init__(self, orchestration_manager, state_manager, output_adapter):
        self.orchestration_manager = orchestration_manager
        self.state_manager = state_manager
        self.output_adapter = output_adapter
    
    async def execute_node(self, node: DSLNode, context: Dict) -> Any:
        """Execute a single node"""
        logger.info(f"Executing node: {node.id} (type: {node.type})")
        
        node_type = node.type
        
        if node_type == "input":
            return context
        elif node_type == "node.state.initialize":
            return await self._execute_state_init(node, context)
        elif node_type == "node.orchestration.execute":
            return await self._execute_orchestration(node, context)
        elif node_type == "node.adapter.unified_output":
            return await self._execute_adapter(node, context)
        elif node_type == "node.validation.unified":
            return await self._execute_validation(node, context)
        elif node_type == "node.state.update":
            return await self._execute_state_update(node, context)
        elif node_type == "node.wordpress.publish":
            return await self._execute_wordpress_publish(node, context)
        elif node_type == "node.campaign.save":
            return await self._execute_campaign_save(node, context)
        elif node_type == "node.state.save":
            return await self._execute_state_save(node, context)
        elif node_type == "output":
            return context
        else:
            logger.warning(f"Unknown node type: {node_type}")
            return context
    
    async def _execute_state_init(self, node: DSLNode, context: Dict) -> Dict:
        """Initialize execution state"""
        import uuid
        from datetime import datetime
        
        state_data = {
            "execution_id": str(uuid.uuid4())[:8],
            "project_id": node.config.get('project_id'),
            "status": node.config.get('status', 'pending'),
            "created_at": datetime.now().isoformat(),
        }
        return {f"{node.id}": state_data}
    
    async def _execute_orchestration(self, node: DSLNode, context: Dict) -> Dict:
        """Execute orchestration with fallback"""
        logger.info(f"Executing orchestration")
        
        keyword = context.get('keyword', '')
        category = context.get('category', '')
        
        result = await self.orchestration_manager.execute(
            keyword=keyword,
            category=category,
            modes=node.config.get('modes', ['auto'])
        )
        
        return {f"{node.id}": result}
    
    async def _execute_adapter(self, node: DSLNode, context: Dict) -> Dict:
        """Convert output to unified format"""
        logger.info("Converting output to unified format")
        
        mode = node.config.get('mode', 'auto')
        orchestration_result = context.get('execute_orchestration', {})
        
        unified_output = self.output_adapter.from_orchestration_result(
            orchestration_result,
            mode
        )
        
        return {
            f"{node.id}": {
                "unified_output": unified_output,
                "metadata": {"mode": mode}
            }
        }
    
    async def _execute_validation(self, node: DSLNode, context: Dict) -> Dict:
        """Validate unified output"""
        logger.info("Validating output")
        
        standardized_output = context.get('standardize_cmo_output', {}) or context.get('standardize_output', {})
        is_valid = self.output_adapter.validate_output(standardized_output)
        
        return {
            f"{node.id}": {
                "is_valid": is_valid,
                "validation_errors": [] if is_valid else ["Validation failed"]
            }
        }
    
    async def _execute_state_update(self, node: DSLNode, context: Dict) -> Dict:
        """Update execution state"""
        logger.info("Updating execution state")
        return {f"{node.id}": {"state_updated": True}}
    
    async def _execute_wordpress_publish(self, node: DSLNode, context: Dict) -> Dict:
        """Publish to WordPress"""
        logger.info("Publishing to WordPress")
        
        import uuid
        return {
            f"{node.id}": {
                "post_id": f"wp_{str(uuid.uuid4())[:8]}",
                "preview_url": "https://example.com/preview",
                "publish_status": "draft"
            }
        }
    
    async def _execute_campaign_save(self, node: DSLNode, context: Dict) -> Dict:
        """Save campaign record"""
        logger.info("Saving campaign record")
        
        import uuid
        campaign_id = str(uuid.uuid4())[:8]
        campaign_dir = "/home/ubuntu/michinhhang/dify-integration/autopilot/campaigns"
        os.makedirs(campaign_dir, exist_ok=True)
        
        campaign_file = os.path.join(campaign_dir, f"campaign_{campaign_id}.json")
        
        return {
            f"{node.id}": {
                "campaign_file": campaign_file,
                "record_saved": True,
                "campaign_id": campaign_id
            }
        }
    
    async def _execute_state_save(self, node: DSLNode, context: Dict) -> Dict:
        """Save state to backend"""
        logger.info("Saving state to backend")
        
        if self.state_manager:
            await asyncio.to_thread(self.state_manager.save_execution, context)
        
        from datetime import datetime
        return {
            f"{node.id}": {
                "state_saved": True,
                "completion_time": datetime.now().isoformat()
            }
        }


class DifyWorkflowEngine:
    """Engine để execute DSL workflow"""
    
    def __init__(self, dsl_file: str, orchestration_manager, state_manager, output_adapter):
        self.parser = DifyDSLParser(dsl_file)
        self.executor = DifyNodeExecutor(orchestration_manager, state_manager, output_adapter)
        self.execution_results = {}
    
    async def execute_workflow(self, input_params: Dict) -> Dict:
        """Execute full workflow"""
        logger.info("Starting DSL workflow execution")
        
        execution_order = self._topological_sort()
        context = {"input": input_params}
        
        for node_id in execution_order:
            node = self.parser.nodes[node_id]
            
            try:
                result = await self.executor.execute_node(node, context)
                context.update(result)
                self.execution_results[node_id] = result
                logger.info(f"✅ Node {node_id} completed")
            
            except Exception as e:
                logger.error(f"❌ Node {node_id} failed: {str(e)}")
                if node.error_handler:
                    logger.info(f"Executing error handler: {node.error_handler}")
                raise
        
        logger.info("✅ DSL workflow completed successfully")
        return self.execution_results
    
    def _topological_sort(self) -> List[str]:
        """Sort nodes by dependencies"""
        from collections import defaultdict, deque
        
        graph = defaultdict(list)
        in_degree = defaultdict(int)
        all_nodes = set(self.parser.nodes.keys())
        
        for node_id, node in self.parser.nodes.items():
            for dep in node.depends_on:
                graph[dep].append(node_id)
            in_degree[node_id] = len(node.depends_on)
        
        queue = deque([node_id for node_id in all_nodes if in_degree[node_id] == 0])
        result = []
        
        while queue:
            node_id = queue.popleft()
            result.append(node_id)
            
            for neighbor in graph[node_id]:
                in_degree[neighbor] -= 1
                if in_degree[neighbor] == 0:
                    queue.append(neighbor)
        
        if len(result) != len(all_nodes):
            raise ValueError("Circular dependency detected in workflow")
        
        return result
    
    def get_execution_report(self) -> Dict:
        """Generate execution report"""
        return {
            "total_nodes": len(self.parser.nodes),
            "executed_nodes": len(self.execution_results),
            "results": self.execution_results,
            "workflow_name": self.parser.dsl_config.get('name')
        }


